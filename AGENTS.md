# Repository instructions for agents

These instructions apply throughout this repository.

Read [CONTRIBUTING.md](CONTRIBUTING.md) before changing files. It is the canonical development contract for humans and agents; do not duplicate its rules in new instruction files.

- Use English for all repository and plugin development artifacts, with the localization and upstream exceptions defined in the contract. Conversation replies may follow the user's requested language.
- Inspect the working tree and relevant implementation first. Preserve unrelated work. State the intended change, risks and rollback before editing; keep the patch focused.
- Implement plugin code in `plugin/trunk/`; keep tooling and documentation outside the installable tree. Preserve existing public contracts and declared compatibility.
- WordPress.org SVN is strictly read-only. This repository's workflow does not authorize publishing or production changes.
- Never read out or commit local secrets. Do not modify ordinary local databases or remove volumes as a side effect of an unrelated task.
- Run checks appropriate to the change and report actual results, unverified assumptions and remaining limitations. Follow the contract's review and release boundaries.

Operational references:

- [Local development and smoke tests](docs/development.md)
- [Packaging, release and rollback](docs/release.md)
- [Imported source provenance](docs/normalization.md)
