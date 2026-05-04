# Contribution Guidelines

We welcome contributions! Here is how you can help:

- **Bug Reports & Feature Requests**: Use the Issues tab on GitHub to report bugs or suggest new features. Please provide as much detail as possible.

- **Code Contributions**:
  - Fork the repository and create your feature branch from `main`.
  - Ensure the code adheres to existing conventions and style.
  - Run all tests to ensure nothing is broken.
  - Submit a pull request to `main` including detailed changes and link to any related issues.

- **Documentation**:
  - Improve or expand the documentation. We are especially interested in detailed how-to guides.
  - Ensure that any new feature or significant change has documentation.

- **Community**:
  - Join discussions on GitHub Discussions.
  - Help others by responding to issues, reviewing PRs, or updating docs.

---

## Setup Instructions

For complete development setup instructions, code style, testing, and PR guidelines, please refer to the root [CONTRIBUTING.md](../CONTRIBUTING.md) and the [Getting Started](./getting-started.md) guide.

Quick start:
```bash
git clone https://github.com/Samireltabal/aiPal.git
cd aiPal
composer install
npm install
cp .env.example .env
# Start supporting services and run `composer run dev`
```

See [CONTRIBUTING.md](../CONTRIBUTING.md) for full details on Docker services, Pint, tests, and conventional commits.

---

## Pull Request Guidelines

- **Structure**: Use meaningful commit messages and well-structured branches.
- **Documentation**: Update documentation for any new feature or API changes.
- **Merge**: Ensure your branch is up-to-date with `main` and fully tested before requesting a merge.
