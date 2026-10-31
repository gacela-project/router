# Contributing

Thanks for helping. Bug reports, fixes, docs and new ideas are all welcome.

## Set up

You need PHP 8.3 or newer and Composer.

```bash
git clone https://github.com/gacela-project/router.git
cd router
composer install
```

To see the router work, run `composer serve` and open <http://localhost:8081>. The routes are in `example/example.php`.

## Run the checks

Run `composer test` before you push. It checks formatting, then runs Psalm, PHPStan and PHPUnit, the same as CI.

| Command | What it does |
|---|---|
| `composer test` | Formatting check, Psalm, PHPStan and PHPUnit |
| `composer test-phpunit -- --filter name` | One test or one test class |
| `composer csfix` | Fixes formatting with php-cs-fixer |
| `composer infection` | Mutation tests. Needs Xdebug or PCOV. CI runs it too and fails below 96% |

Run Psalm and PHPStan one after the other, not at the same time.

## Make a change

1. Branch from `main`. Name the branch `fix/<issue>-short-name`, `feat/<issue>-short-name` or `docs/<issue>-short-name`.
2. Add a test next to the ones for the same code. `tests/Unit` covers one class, and `tests/Feature` drives the router through `Router::run()`. Tests that check headers extend `HeaderTestCase`.
3. If users can see the change, update the matching page in `docs/` and add a line under `[Unreleased]` in `CHANGELOG.md`.
4. Commit with a [conventional commit](https://www.conventionalcommits.org/) message, for example `fix(routing): match a trailing optional param only at a segment boundary`. Mark a breaking change with `!` after the type.
5. Open a pull request and fill in the template. CI runs formatting, static analysis and the tests on PHP 8.3, 8.4 and 8.5, with locked and with the newest dependencies.

Keep each pull request to one issue. Put unrelated findings in a new issue.

## Report a bug

Open an issue with:

- what you did, with the smallest code that shows it
- what you expected
- what happened instead, as text and not as a screenshot
- the PHP and router versions

If you know why it happens, say so.

## Work with an AI coding tool

The shared instructions for Claude Code and Codex live in `.agnostic-ai/`. Edit them there and run `agnostic-ai sync`. The generated files, such as `CLAUDE.md` and `AGENTS.md`, are ignored by Git.

## Code of conduct and license

This project follows the [Code of Conduct](CODE_OF_CONDUCT.md). Your contributions are released under the [MIT license](../LICENSE).
