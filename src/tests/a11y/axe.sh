#!/usr/bin/env bash
#
# AQDesk accessibility audit with axe-core (roadmap 4.3).
#
# Starts the real application on a throw-away SQLite database (like the e2e
# smoke test), saves the main pages as a visitor, a member and an
# administrator, and runs axe's WCAG 2.1 A/AA rules on each.
#
# Usage (Git Bash, from src/):   bash tests/a11y/axe.sh
# Needs Node.js and the dev packages (npm install). Exit code 0 = no violations.

set -u
cd "$(dirname "$0")/../.."
export PATH="/c/Program Files/nodejs:$PATH"

source tests/e2e/server.sh
start_server

OUT="$WORK/axe"
rm -rf "$OUT" && mkdir -p "$OUT"

signin() {   # signin who email → cookie jar $WORK/<who>.jar
    local jar="$WORK/$1.jar" tok
    rm -f "$jar"
    tok=$(curl -s -c "$jar" -b "$jar" "$B/login" | sed -n 's/.*name="csrf-token" content="\([^"]*\)".*/\1/p' | head -1)
    curl -s -o /dev/null -c "$jar" -b "$jar" --data-urlencode "_token=$tok" --data-urlencode "email=$2" --data-urlencode "password=password" "$B/login"
}
save() {     # save who path
    local name; name="$1$(echo "$2" | tr '/?=&' '____')"
    curl -s -b "$WORK/$1.jar" -c "$WORK/$1.jar" -o "$OUT/${name:-root}.html" "$B$2"
}

touch "$WORK/guest.jar"
signin member member@aqdesk.test
signin expert expert@aqdesk.test
signin admin admin@aqdesk.test

for p in / /questions /tags /knowledge /learn /learn/resources /jobs /organizations /events /podcasts /consultants /newsletter /updates \
         /login /register /forgot-password /accessibility /guidelines /privacy; do save guest "$p"; done
for p in /dashboard /questions/create /saved /applications /projects /consulting/sessions /consulting/proposals /notifications /settings /profile /privacy/my-data; do save member "$p"; done
for p in /forms /forms/new /events/new /consulting/profile /consulting/availability /consulting/clients /knowledge/write /my-jobs; do save expert "$p"; done
for p in /dashboard /admin/users /admin/roles /admin/reports /admin/newsletter /admin/newsletter/new /admin/announcements/new /admin/activity; do save admin "$p"; done

# Detail pages: the first link of each kind on its list page.
for list in /questions /jobs /events /podcasts /consultants /knowledge /learn; do
    link=$(grep -oE "href=\"$B$list/[a-z0-9-]+\"" "$OUT/guest$(echo "$list" | tr '/' '_').html" | grep -vE '/(create|new|write|mine|resources)"' | head -1 | sed "s#href=\"$B##; s#\"##")
    [ -n "$link" ] && save guest "$link"
done

node tests/a11y/axe-run.mjs "$OUT"/*.html
