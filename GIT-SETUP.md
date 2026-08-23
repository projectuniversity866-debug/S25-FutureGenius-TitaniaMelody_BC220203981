# Pushing AQDesk to your private GitHub repository

**Repository name:** `S25-FutureGenius-TitaniaMelody_BC220203981`
**Student:** Titania Melody · BC220203981
**Supervisor:** Mr. Abdullah Qamar

> **One thing to confirm on Google Chat first:** the assignment writes the prefix as
> `S25`, but this is the **Spring 2026** semester — that looks like leftover text from a
> previous term. Ask Mr. Qamar whether he wants `S25` (exactly as written) or `S26`.
> Also confirm whether your group submits **one** repository between you or **one per
> member**, since the assignment names the repo `[YourName_ID]` but grades "individual
> effort". Renaming a GitHub repo later is easy, so don't let this block you — push now,
> rename if he says so.

---

## Option A — GitHub Desktop (no commands)

1. Install **GitHub Desktop** from https://desktop.github.com and sign in.
2. **File → Add local repository**, choose your extracted `aqdesk` folder.
   When it says the folder isn't a repository, click **create a repository**.
3. Name it `S25-FutureGenius-TitaniaMelody_BC220203981`, then **Create repository**.
4. Enter a summary — *"Prototype phase: six mandatory modules"* — and **Commit to main**.
5. Click **Publish repository**.
6. **Tick "Keep this code private".** The assignment requires a private repo.
7. Click **Publish repository** again to confirm.

## Option B — command line

Open a Command Prompt in the extracted project folder:

```bash
git init
git branch -M main
git add .
git commit -m "Prototype phase: six mandatory modules, schema, documentation and presentation"
```

Create the repository at https://github.com/new — name it
`S25-FutureGenius-TitaniaMelody_BC220203981`, set **Private**, and do **not** tick
"Add a README" (you already have one). Then:

```bash
git remote add origin https://github.com/<your-username>/S25-FutureGenius-TitaniaMelody_BC220203981.git
git push -u origin main
```

If git asks you to identify yourself first:

```bash
git config --global user.name "Titania Melody"
git config --global user.email "your.email@example.com"
```

---

## Then: grant your supervisor access

**Settings → Collaborators → Add people**, and add Mr. Qamar's GitHub username.
Ask him for it on Google Chat — the assignment says to arrange access there.

A private repo he cannot open counts as not submitted, so do this the same day you push
and check he confirms.

---

## Check what you pushed

Open the repository in a browser. You should see:

- `docs/` — the documentation and six diagrams
- `src/` — the Laravel source
- `schema/aqdesk_schema.sql`
- `README.md` rendering with all five required sections
- `presentation.pptx`

There should be **no `vendor/` folder** — that is correct and intentional.

## Why `vendor/` is not committed

`vendor/` holds third-party libraries downloaded by Composer. Standard Laravel practice —
and Laravel's own `.gitignore` — excludes it. Anyone cloning the repository runs
`composer install` to recreate it. Committing it would add tens of thousands of files
that are not your work. If your supervisor asks, that is the answer.

## What does NOT go to GitHub

- `AQDesk-Prototype-Submission.zip` — that file is the **LMS** upload, not a repo file
- `vendor/` and `node_modules/` — generated, gitignored
- `.env` — it holds local database credentials and is gitignored (`.env.example` is committed instead)
