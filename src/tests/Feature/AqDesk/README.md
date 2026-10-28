# AQDesk feature tests

The PHPUnit feature suite, one file per area of a module (suite IDs such as
BK or PR are used in `ROADMAP.md`'s progress log).

```bash
cd src
php vendor/bin/phpunit                        # all tests, in-memory SQLite
php vendor/bin/phpunit --filter ProposalTest  # one suite
```

The tests run on an in-memory SQLite database (`phpunit.xml`), so your MySQL
data is never touched. `AqDeskTestCase` seeds the demo data before each test
and offers helpers (`member()`, `expert()`, `askQuestionAs()`, `signOut()` …).

The catalogue per module, code coverage and performance checks are in
[`docs/testing/TEST-SUITE.md`](../../../../docs/testing/TEST-SUITE.md).
