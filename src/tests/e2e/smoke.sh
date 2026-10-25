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
login() {      # login who email
    rm -f "$(jar "$1")"
    send "$1" POST /login --data-urlencode "email=$2" --data-urlencode "password=password" | cut -d' ' -f1
}
path_of() { echo "${1#* }" | sed "s#^$B##"; }

echo "Guest"
for p in / /questions /jobs /login /register /modules/projects; do check "GET $p" 200 "$(get guest "$p")"; done
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
