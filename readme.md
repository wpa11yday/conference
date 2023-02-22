# WP Accessibility Day Conference Plugin

Conference scheduler plugin for WP Accessibility Day

[![Code Linting](https://github.com/WP-Accessibility-Day/conference/actions/workflows/main.yml/badge.svg)](https://github.com/WP-Accessibility-Day/conference/actions/workflows/main.yml)  ![Docs Built](https://github.com/WP-Accessibility-Day/conference/actions/workflows/build-docs.yml/badge.svg)  [![License](https://img.shields.io/badge/license-GPL--2.0%2B-green.svg)](https://www.gnu.org/license/gpl-2.0.html)

This plugin is used by the WP Accessibility Day conference to manage organizers, presenters, and sessions.

## Precommit and Lint Staged

This project uses a precommit hook controlled with [Husky](https://www.npmjs.com/package/husky) to trigger [lint-staged](https://www.npmjs.com/package/lint-staged) commands.

Before committing, staged files will be checked to see if any match the file types and locations in the package.json file and then the appropriate lint command is triggered for those files. Where possible code standards are automatically fixed. If there are remaining errors after attempting to fix, the commit will fail with details about what needs to be resolved.

At times it might be appropriate to ignore an error. In most cases it is best to use inline comments for the linting tool in order to ignore the error for a given line of code.

In very rare cases it may be required to commit a change without resolving the lint errors. In those instances attempt to commit again with the `--no-verify` flag.