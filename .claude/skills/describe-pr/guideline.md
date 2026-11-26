# PR description guide

The description introduces a reviewer to the problem and the reasoning. It never walks through the diff.

## Shortest draft first

A reviewer skims, and the usual failure is a draft that is too long. Start with the fewest sections, and add one only when it tells the reviewer something the title and the code do not. When in doubt, leave it out. The example at the end is the upper limit, not a target.

## Title

A plain description of what the PR does, for a reviewer, in the imperative. No issue ID, no type prefix.

Example: `Limit product descriptions by UTF-8 bytes, not characters`.

## Changelog

No changelog line: the `readme.txt` changelog is written at release time. Still describe a user-visible change in words a merchant would understand, so the release changelog can reuse them.

## Headings

Every section is optional; leaving it out is the default. Most PRs need two or three.

````template
# Description
## 🐛 The problem      a defect: the symptom in plain terms, not the stack trace
## 🎯 The goal         no defect: a new or changed spec
## 💡 Why it happens   the root cause of the symptom or gap
## ✅ The fix          what the change does conceptually and where; never a code walkthrough
## 🔒 Compatibility    required when a hook, stored data, or site state changes: backward compatibility and multisite
## 🧪 Tests            what the tests now cover and what they do not, and why; not a test list
## 🧪 How to verify    rare: at most 3 steps for a product check a reviewer cannot guess
## 🖼️ Visual proof     placeholder for before/after media, only for a UI change and when the user opts in
````

- Use `The problem` or `The goal`, never both.
- **Compatibility:** the one required section, whenever `AGENTS.md` asks for a backward-compatibility or multisite statement. Say what an existing site or a third-party consumer of the hook sees after the update, and whether it was tested on multisite.
- For refactors, features, test additions, and chores, keep only the headings that carry meaning. A test-addition PR often needs only `# Description` and `🧪 Tests`.
- **How to verify:** leave it out by default: QA tests the change. Add it only when a reviewer needs a product check they cannot guess, as at most 3 steps (setting to change, page to open, what they see), with no setup boilerplate and no expected pass/fail results. Never a CLI command (`npm`, `composer`, `phpunit`, `ddev`, ...) anywhere in the description.
- **Visual proof:** never drafted. Offer it only for a visible UI change; if the user opts in, append this placeholder for them to fill in; never invent media:

````template
## 🖼️ Visual proof

| Before                | After                 |
|-----------------------|-----------------------|
| _screenshot or video_ | _screenshot or video_ |
````

## Content

- Explain the problem and the reason; the diff shows the how. No file-by-file summary.
- The description is public: no internal issue IDs, chat threads, developer names, or other private details.
- Describe the state after the merge. No "currently failing", "WIP", or "green once X".

## Style

- Plain, direct, active English. When plain words and technical precision conflict, choose plain words. Paragraphs of 1-3 sentences. No filler ("it is worth noting", "in order to"), no AI words ("delve", "scrutinize"), no em dashes.
- Backticks for paths, classes, methods, hooks, config keys, and JS globals. Bold at most once per section.
- Bullets for 3 or more parallel items.
- No soft wrapping: each paragraph and bullet is one line. GitHub shows every line break.

## Example

A subtle fix with five sections: about the longest a description gets. A routine PR has one or two, and no "How to verify".

````example
Title: Limit product descriptions by UTF-8 bytes, not characters

# Description

## 🐛 The problem

Some products fail to sync to PayPal POS with a validation error, although their description looks short enough. It happens with emoji and with non-Latin scripts.

## 💡 Why it happens

The PayPal POS API limits the description in **bytes**, but `DescriptionLengthFilter` cut it to the limit in characters. A multibyte character counts once for the filter and up to four times for the API.

## ✅ The fix

`DescriptionLengthFilter` now cuts the description to the byte limit, on a character boundary, so no character is split in half. Plain ASCII descriptions are unchanged.

## 🧪 Tests

`DescriptionLengthFilterTest` covers ASCII, multibyte, and emoji descriptions at, below, and above the limit.

## 🧪 How to verify

1. Give a product a description of about 1,000 emoji and save it.
2. The product syncs to PayPal POS, with the description cut short.
````
