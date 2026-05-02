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
  - Join discussions on GitHub Discussions and our community chat at [Community Chat](https://example.com/community-chat).
  - Help others by responding to issues, reviewing PRs, or updating docs.

---

## Setup Instructions

To start developing locally, clone the repository and install dependencies:
```bash
git clone https://github.com/Samireltabal/aiPal.git
cd aiPal
composer install
npm install
```

### Code Style

Ensure your code follows our coding standards:
```bash
vendor/bin/pint
```

### Testing

Run the full test suite to verify your changes:
```bash
php artisan test --compact
```

---

## Pull Request Guidelines

- **Structure**: Use meaningful commit messages and well-structured branches.
- **Documentation**: Update documentation for any new feature or API changes.
- **Merge**: Ensure your branch is up-to-date with `main` and fully tested before requesting a merge.
