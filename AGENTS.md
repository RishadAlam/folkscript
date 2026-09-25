# Project instructions

Read PRODUCT.md and DESIGN.md for conventions, and INSTALL.md for setup.

Use Laravel, Blade, and Livewire. Folkscript is free, open source, and nonprofit; every published story is freely readable. Enforce account authorization and private-content access on the server.

Preserve unrelated changes. Run relevant existing checks and verify interface changes in a browser. Tests must use an isolated in-memory SQLite database, never a developer's or production database. Never commit credentials, local environment files, databases, or uploaded user content.

Commit each completed, verified step as a focused commit. Publish or push only when requested. Use RTK to reduce terminal output when available; it is optional tooling, not an application dependency.
