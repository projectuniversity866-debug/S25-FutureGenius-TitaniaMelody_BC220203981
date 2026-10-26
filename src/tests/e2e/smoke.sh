#!/usr/bin/env bash
#
# AQDesk end-to-end smoke test.
#
# Starts the real application on a throw-away SQLite database (your MySQL data
# is never touched), then drives it over HTTP exactly as a browser would:
# signs in as guest / member / expert / admin, submits the real forms with
# CSRF tokens and checks every response.
#
# Usage (Git Bash, from src/):   bash tests/e2e/smoke.sh
# Optional:  PHP=/c/wamp64/bin/php/php8.3.28/php.exe  PORT=8765
#
# Exit code 0 = all checks passed.

set -u
cd "$(dirname "$0")/../.."

source tests/e2e/server.sh
start_server

PASS=0; FAIL=0
check() {   # check "description" expected actual
    if [ "$2" = "$3" ]; then PASS=$((PASS + 1)); printf '  \033[32m✓\033[0m %s\n' "$1"
    else FAIL=$((FAIL + 1)); printf '  \033[31m✗ %s — expected [%s] got [%s]\033[0m\n' "$1" "$2" "$3"; fi
}
contains() {   # contains "description" file needle
    if grep -qF -- "$3" "$2"; then check "$1" yes yes; else check "$1" yes no; fi
}

jar() { echo "$WORK/$1.jar"; }
php_errors() { # any PHP warning/error page counts as a failure, whatever its status code
    if grep -qE "xdebug-error|<b>(Warning|Notice|Deprecated|Fatal error)</b>" "$WORK/last.html"; then
        echo "$1" >> "$WORK/php-errors.txt"
    fi
}
get() {        # get who path  → status code; body saved to $WORK/last.html
    curl -s -o "$WORK/last.html" -w '%{http_code}' -c "$(jar "$1")" -b "$(jar "$1")" "$B$2"
    php_errors "GET $2"
}
token() {      # current CSRF token for a session
    curl -s -c "$(jar "$1")" -b "$(jar "$1")" "$B/" | sed -n 's/.*name="csrf-token" content="\([^"]*\)".*/\1/p' | head -1
}
send() {       # send who METHOD path [curl -d args…] → "code location"
    local who=$1 method=$2 path=$3; shift 3
    local tok; tok=$(token "$who")
    local spoof=()
    [ "$method" != POST ] && spoof=(--data-urlencode "_method=$method")
    curl -s -o "$WORK/last.html" -w '%{http_code} %{redirect_url}' -c "$(jar "$who")" -b "$(jar "$who")" \
        --data-urlencode "_token=$tok" "${spoof[@]}" "$@" "$B$path"
    php_errors "$method $path"
}
json() {       # json who METHOD path [body] → status code; body saved to $WORK/last.json (what the Vue islands send)
    local tok; tok=$(token "$1")
    curl -s -o "$WORK/last.json" -w '%{http_code}' -c "$(jar "$1")" -b "$(jar "$1")" -X "$2" \
        -H 'Accept: application/json' -H 'Content-Type: application/json' -H "X-CSRF-TOKEN: $tok" \
        ${4:+--data "$4"} "$B$3"
}
upload() {     # upload who path [curl -F args…] → "code location" (multipart form, like a file input)
    local who=$1 path=$2; shift 2
    curl -s -o "$WORK/last.html" -w '%{http_code} %{redirect_url}' -c "$(jar "$who")" -b "$(jar "$who")" \
        -F "_token=$(token "$who")" "$@" "$B$path"
    php_errors "POST $path"
}
login() {      # login who email
    rm -f "$(jar "$1")"
    send "$1" POST /login --data-urlencode "email=$2" --data-urlencode "password=password" | cut -d' ' -f1
}
path_of() { echo "${1#* }" | sed "s#^$B##"; }

echo "Guest"
for p in / /questions /jobs /login /register /modules/events /accessibility; do check "GET $p" 200 "$(get guest "$p")"; done
check "dashboard needs sign-in" 302 "$(get guest /dashboard)"
check "unknown module is 404" 404 "$(get guest /modules/nope)"

echo "Authentication"
check "member signs in" 302 "$(login member member@aqdesk.test)"
check "member dashboard" 200 "$(get member /dashboard)"
check "wrong password is refused" 302 "$(rm -f "$(jar bad)"; send bad POST /login --data-urlencode email=member@aqdesk.test --data-urlencode password=nope | cut -d' ' -f1)"
check "…and stays signed out" 302 "$(get bad /dashboard)"
login sara sara@aqdesk.test >/dev/null
login expert expert@aqdesk.test >/dev/null
login admin admin@aqdesk.test >/dev/null

echo "Community Q&A"
check "ask form" 200 "$(get member /questions/create)"
contains "ask form mounts the Vue character counter" "$WORK/last.html" 'data-vue="CharacterCounter"'
contains "ask form loads the built JavaScript" "$WORK/last.html" '/build/assets/app-'
R=$(send member POST /questions --data-urlencode "title=How should an e2e smoke test sign in to Laravel" \
    --data-urlencode "category_id=1" --data-urlencode "body=It needs a CSRF token and a cookie jar to keep the session." \
    --data-urlencode "tags=testing, laravel")
check "member posts a question" 302 "${R%% *}"
Q=$(path_of "$R")
check "question page" 200 "$(get guest "$Q")"
contains "question title shown" "$WORK/last.html" "How should an e2e smoke test sign in to Laravel"
check "too-short question rejected" 302 "$(send member POST /questions --data-urlencode title=short --data-urlencode category_id=1 --data-urlencode body=tiny | cut -d' ' -f1)"
check "another member cannot edit it" 403 "$(get sara "$Q/edit")"
check "author can edit it" 200 "$(get member "$Q/edit")"

check "sara answers" 302 "$(send sara POST "$Q/answers" --data-urlencode "body=Use curl with -c and -b and read the token from the meta tag." | cut -d' ' -f1)"
get guest "$Q" >/dev/null
contains "answer shown" "$WORK/last.html" "Use curl with -c and -b"
check "sara upvotes the question" 302 "$(send sara POST "$Q/vote" --data-urlencode value=1 | cut -d' ' -f1)"
send member POST "$Q/vote" --data-urlencode value=1 >/dev/null; get member "$Q" >/dev/null
contains "author cannot vote on own question" "$WORK/last.html" "You cannot vote on your own post."
get member "$Q" >/dev/null
A=$(grep -oE '/answers/[0-9]+/accept' "$WORK/last.html" | head -1)
check "author sees the Accept button" yes "$([ -n "$A" ] && echo yes || echo no)"
check "sara cannot accept" 403 "$(send sara POST "$A" | cut -d' ' -f1)"
check "author accepts the answer" 302 "$(send member POST "$A" | cut -d' ' -f1)"

echo "Discovery & live updates"
for s in trending votes unanswered; do check "GET /questions?sort=$s" 200 "$(get guest "/questions?sort=$s")"; done
get guest "/questions?sort=trending" >/dev/null
contains "trending lists the question answered just now" "$WORK/last.html" "How should an e2e smoke test sign in to Laravel"
check "tag directory" 200 "$(get guest /tags)"
check "sara follows the testing tag" 302 "$(send sara POST /tags/testing/follow | cut -d' ' -f1)"
get sara "/questions?tag=testing" >/dev/null
contains "tag header shows she follows it" "$WORK/last.html" "Following testing, select to unfollow"
check "sara saves the question" 302 "$(send sara POST "$Q/bookmark" | cut -d' ' -f1)"
check "saved page" 200 "$(get sara /saved)"
contains "saved question listed" "$WORK/last.html" "How should an e2e smoke test sign in to Laravel"
check "saved page needs sign-in" 302 "$(get guest /saved)"
R=$(send expert POST /questions --data-urlencode "title=Which smoke test runner suits a Laravel monolith" \
    --data-urlencode "category_id=1" --data-urlencode "body=Looking for a light way to drive the real app over HTTP in CI." \
    --data-urlencode "tags=testing")
check "expert asks in the testing tag" 302 "${R%% *}"
check "sara's bell summary (JSON)" 200 "$(json sara GET /notifications/summary)"
contains "…tells her about the new question" "$WORK/last.json" "New question tagged testing"
check "expert votes without a reload (JSON)" 200 "$(json expert POST "$Q/vote" '{"value":1}')"
contains "…and gets the new score back" "$WORK/last.json" '"vote":1'
check "own-post vote refused (JSON 422)" 422 "$(json member POST "$Q/vote" '{"value":1}')"
contains "…with the reason" "$WORK/last.json" "You cannot vote on your own post."

echo "Jobs & opportunities"
check "member cannot open the post-a-job form" 403 "$(get member /jobs/create)"
R=$(send expert POST /jobs --data-urlencode "title=Junior QA Automation Engineer" --data-urlencode "company=AQDesk Labs" \
    --data-urlencode type=job --data-urlencode work_mode=remote --data-urlencode "location=Lahore" \
    --data-urlencode "description=Write and maintain automated end-to-end tests for the platform." \
    --data-urlencode "deadline=$(date -d '+30 days' +%F 2>/dev/null || date -v+30d +%F)")
check "expert publishes a job" 302 "${R%% *}"
J=$(path_of "$R")
check "job page" 200 "$(get guest "$J")"
check "poster cannot apply to own job" 403 "$(get expert "$J/apply")"
check "apply form" 200 "$(get member "$J/apply")"
contains "apply form mounts the character counter" "$WORK/last.html" 'data-vue="CharacterCounter"'
check "member applies" 302 "$(send member POST "$J/apply" --data-urlencode "cover_letter=I have written end-to-end tests for two Laravel projects and enjoy it." | cut -d' ' -f1)"
R=$(get member "$J/apply")
check "re-opening the form returns to the job" 302 "$R"
check "my applications" 200 "$(get member /applications)"
contains "application listed" "$WORK/last.html" "Junior QA Automation Engineer"
check "applicants page (poster)" 200 "$(get expert "$J/applicants")"
contains "applicant shown" "$WORK/last.html" "Usman Tariq"
APP=$(grep -oE '/applications/[0-9]+/status' "$WORK/last.html" | head -1)
check "poster shortlists the applicant" 302 "$(send expert PUT "$APP" --data-urlencode status=shortlisted | cut -d' ' -f1)"
check "another member cannot see applicants" 403 "$(get sara "$J/applicants")"
check "poster opens the application" 200 "$(get expert "${APP%/status}")"
check "poster adds a private note" 302 "$(send expert POST "${APP%/status}/notes" --data-urlencode "body=Strong testing background." | cut -d' ' -f1)"
check "applicant sees the progress page" 200 "$(get member "${APP%/status}")"
contains "…with the shortlisted step" "$WORK/last.html" 'aria-current="step"'
check "…but not the private note" no "$(grep -qF 'Strong testing background.' "$WORK/last.html" && echo yes || echo no)"
check "another member cannot open it" 403 "$(get sara "${APP%/status}")"
check "member saves the opportunity" 302 "$(send member POST "$J/save" | cut -d' ' -f1)"
check "saved opportunities tab" 200 "$(get member "/saved?tab=opportunities")"
contains "…lists it" "$WORK/last.html" "Junior QA Automation Engineer"
check "sara creates a job alert" 302 "$(send sara POST /job-alerts --data-urlencode "keywords=hackathon" | cut -d' ' -f1)"
check "job alerts page" 200 "$(get sara /job-alerts)"

echo "Organizations & opportunity types"
check "organization directory" 200 "$(get guest /organizations)"
check "member cannot add an organization" 403 "$(get member /organizations/create)"
R=$(send expert POST /organizations --data-urlencode "name=AQDesk Test Labs" --data-urlencode "tagline=Testing the platform end to end" \
    --data-urlencode "website=https://aqdesk-labs.example.org" --data-urlencode "size=11-50")
check "expert creates an organization" 302 "${R%% *}"
O=$(path_of "$R")
ORG_ID=$(grep -oE '<option value="[0-9]+"' <(curl -s -b "$(jar expert)" "$B/jobs/create") | head -1 | grep -oE '[0-9]+')
R=$(send expert POST /jobs --data-urlencode "title=Weekend Hackathon on Accessible Apps" --data-urlencode "organization_id=$ORG_ID" \
    --data-urlencode type=hackathon --data-urlencode work_mode=onsite \
    --data-urlencode "description=Build accessible apps in 48 hours with mentors on site." \
    --data-urlencode "starts_on=$(date -d '+20 days' +%F)" --data-urlencode "ends_on=$(date -d '+22 days' +%F)")
check "expert posts a hackathon under it" 302 "${R%% *}"
check "organization profile" 200 "$(get guest "$O")"
contains "profile lists the hackathon" "$WORK/last.html" "Weekend Hackathon on Accessible Apps"
check "hackathon tab" 200 "$(get guest "/jobs?type=hackathon")"
contains "hackathon tab lists it" "$WORK/last.html" "Weekend Hackathon on Accessible Apps"
check "sara's bell summary (JSON)" 200 "$(json sara GET /notifications/summary)"
contains "…her job alert fired" "$WORK/last.json" "for your alert"
check "deadline closing runs" 0 "$("$PHP" artisan careers:close-expired >/dev/null 2>&1; echo $?)"

echo "Expert verification & Knowledge Hub"
check "knowledge hub" 200 "$(get guest /knowledge)"
contains "…lists the seeded guide" "$WORK/last.html" "evaluating a classifier properly"
check "member cannot write yet" 403 "$(get member /knowledge/write)"
check "member applies for verification" 302 "$(send member POST /expert-verification --data-urlencode "field=Software testing" \
    --data-urlencode "credentials=Two years writing automated end-to-end tests for Laravel applications." | cut -d' ' -f1)"
check "verification queue (admin)" 200 "$(get admin /admin/verifications)"
V=$(grep -oE '/admin/verifications/[0-9]+' "$WORK/last.html" | tail -1)
check "member cannot open the queue" 403 "$(get member /admin/verifications)"
check "admin approves" 302 "$(send admin PUT "$V" --data-urlencode decision=approve | cut -d' ' -f1)"
check "verified member can write now" 200 "$(get member /knowledge/write)"
R=$(send member POST /knowledge --data-urlencode "title=Writing smoke tests that drive real forms" --data-urlencode kind=guide \
    --data-urlencode "summary=Use real CSRF tokens and cookie jars so the test exercises the same path as a browser." \
    --data-urlencode "body=$(printf 'Keep one cookie jar per user and read the CSRF token from the page before every form. %.0s' 1 2 3)" \
    --data-urlencode action=publish)
check "member publishes a guide" 302 "${R%% *}"
check "the guide page" 200 "$(get guest "$(path_of "$R")")"
contains "…shows the verified badge" "$WORK/last.html" "Verified expert"

echo "Learning Hub & forms"
check "learning hub" 200 "$(get guest /learn)"
check "resource library" 200 "$(get guest /learn/resources)"
check "a learning path" 200 "$(get guest /learn/paths/start-with-web-accessibility)"
get sara /learn/paths/start-with-web-accessibility >/dev/null
STEP=$(grep -oE '/learn/steps/[0-9]+/toggle' "$WORK/last.html" | head -1)
check "sara ticks off a step" 302 "$(send sara POST "$STEP" | cut -d' ' -f1)"
get sara /learn/mine >/dev/null
contains "…and sees her progress" "$WORK/last.html" "1 of 4 steps"
check "member cannot build forms" 403 "$(get sara /forms/new)"
R=$(send expert POST /forms --data-urlencode "title=Smoke test sign-up")
check "expert creates a form" 302 "${R%% *}"
F=$(path_of "$R"); F=${F%/edit}
check "expert adds a question" 302 "$(send expert POST "$F/fields" --data-urlencode type=short_text --data-urlencode "label=Your name" --data-urlencode is_required=1 | cut -d' ' -f1)"
check "expert opens the form" 302 "$(send expert PUT "$F/status" --data-urlencode status=open | cut -d' ' -f1)"
SLUG=${F#/forms/}
check "public form page" 200 "$(get guest "/f/$SLUG")"
FIELD=$(grep -oE 'name="answers\[[0-9]+\]"' "$WORK/last.html" | head -1 | grep -oE '[0-9]+')
check "a visitor submits it" 302 "$(send guest POST "/f/$SLUG" --data-urlencode "answers[$FIELD]=Smoke Visitor" | cut -d' ' -f1)"
check "responses page" 200 "$(get expert "$F/responses")"
contains "…shows the answer" "$WORK/last.html" "Smoke Visitor"
check "CSV export" 200 "$(get expert "$F/responses.csv")"

echo "Accessibility & projects"
check "save display options (no JavaScript)" 302 "$(send guest POST /accessibility --data-urlencode text=large --data-urlencode contrast=1 | cut -d' ' -f1)"
get guest /questions >/dev/null
contains "…applied on the next page" "$WORK/last.html" 'class="a11y-text-large a11y-contrast"'
R=$(send member POST /projects --data-urlencode "name=Smoke test project" --data-urlencode visibility=private)
check "member creates a project" 302 "${R%% *}"
P=$(path_of "$R")
check "project page" 200 "$(get member "$P")"
check "owner invites sara as collaborator" 302 "$(send member POST "$P/invitations" --data-urlencode email=sara@aqdesk.test --data-urlencode role=collaborator | cut -d' ' -f1)"
get sara /notifications >/dev/null
NID=$(grep -oE '/notifications/[0-9a-f-]{36}' "$WORK/last.html" | head -1)
INV=$(path_of "$(curl -s -o /dev/null -w '%{http_code} %{redirect_url}' -b "$(jar sara)" "$B$NID")")
check "sara opens the invitation link" 200 "$(get sara "$INV")"
check "sara accepts" 302 "$(send sara POST "$INV/accept" | cut -d' ' -f1)"
check "sara opens the project" 200 "$(get sara "$P")"
printf 'Smoke test plan, first draft.\n' > "$WORK/plan.txt"
check "sara uploads a file" 302 "$(upload sara "$P/files" -F "file=@$WORK/plan.txt;type=text/plain" -F "name=Smoke plan" | cut -d' ' -f1)"
get member "$P" >/dev/null
PF=$(grep -oE "$P/files/[0-9]+" "$WORK/last.html" | head -1)
printf 'Smoke test plan, second draft.\n' > "$WORK/plan.txt"
check "owner uploads version 2" 302 "$(upload member "$PF/versions" -F "file=@$WORK/plan.txt;type=text/plain" -F "note=Second draft" | cut -d' ' -f1)"
check "file page" 200 "$(get member "$PF")"
contains "…lists version 2 as latest" "$WORK/last.html" "v2 · latest"
V1=$(grep -oE "$PF/versions/[0-9]+" "$WORK/last.html" | tail -1)
check "download version 1" 200 "$(get sara "$V1")"
contains "…is the first draft" "$WORK/last.html" "first draft"
check "an outsider cannot download" 404 "$(get expert "$V1")"
check "an outsider gets 404" 404 "$(get expert "$P")"
check "sara leaves" 302 "$(send sara DELETE "$P/leave" | cut -d' ' -f1)"

echo "Governance & privacy"
for p in /guidelines /terms /privacy; do check "GET $p" 200 "$(get guest "$p")"; done
QID=$(grep -oE '/report/question/[0-9]+' <(curl -s -b "$(jar sara)" "$B$Q") | head -1)
check "sara reports the member's question" 302 "$(send sara POST "$QID" --data-urlencode reason=off_topic | cut -d' ' -f1)"
check "moderation queue (admin)" 200 "$(get admin /admin/reports)"
RID=$(grep -oE '/admin/reports/[0-9]+' "$WORK/last.html" | tail -1)
check "member cannot open the queue" 403 "$(get member /admin/reports)"
check "admin dismisses the report" 302 "$(send admin PUT "$RID" --data-urlencode decision=dismiss | cut -d' ' -f1)"
check "moderation log" 200 "$(get admin /admin/moderation-log)"
check "privacy & my data page" 200 "$(get sara /privacy/my-data)"
check "download my data" 200 "$(get sara /privacy/my-data/download)"
contains "…is sara's own data" "$WORK/last.html" '"email": "sara@aqdesk.test"'
check "sara deletes her account" 302 "$(send sara DELETE /privacy/account --data-urlencode password=password --data-urlencode confirm=1 | cut -d' ' -f1)"
check "…and is signed out" 302 "$(get sara /dashboard)"

echo "Administration"
check "member blocked from admin" 403 "$(get member /admin/users)"
for p in /admin/users /admin/questions /admin/jobs; do check "admin GET $p" 200 "$(get admin "$p")"; done
QID=$(grep -oE '/admin/questions/[^/"]+/toggle' <(curl -s -b "$(jar admin)" "$B/admin/questions") | head -1)
check "admin closes a question" 302 "$(send admin PUT "$QID" | cut -d' ' -f1)"

echo "Sign out"
check "member signs out" 302 "$(send member POST /logout | cut -d' ' -f1)"
check "dashboard needs sign-in again" 302 "$(get member /dashboard)"

if [ -s "$WORK/php-errors.txt" ]; then
    FAIL=$((FAIL + 1)); echo "  ✗ PHP warnings/errors on: $(sort -u "$WORK/php-errors.txt" | tr '\n' ' ')"
fi
if grep -qiE "Fatal|Exception" "$WORK/server.log"; then FAIL=$((FAIL + 1)); echo "  ✗ PHP errors in server log:"; grep -iE "Fatal|Exception" "$WORK/server.log" | head -5; fi

echo
echo "e2e smoke: $PASS passed, $FAIL failed"
[ "$FAIL" -eq 0 ]
