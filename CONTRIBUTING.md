# Contributing

Thank you for considering contributing to Laravel Likeable! This document outlines the contribution process and guidelines.

## Code of Conduct

This project adheres to a code of conduct that we expect all contributors to follow. Please be respectful and constructive in your interactions with others.

## How to Contribute

### Reporting Bugs

If you discover a bug, please create an issue on GitHub with:

- A clear, descriptive title
- Steps to reproduce the issue
- Expected behavior
- Actual behavior
- Your environment (PHP version, Laravel version, package version)
- Any relevant code samples or error messages

### Suggesting Features

Feature requests are welcome! Please:

- Check existing issues to avoid duplicates
- Clearly describe the feature and its use case
- Explain how it benefits the package
- Provide examples of how it would be used

### Pull Requests

We actively welcome pull requests. Follow these steps:

1. **Fork the repository**

2. **Create a feature branch**
   ```bash
   git checkout -b feature/your-feature-name
   ```

3. **Make your changes**
   - Write clean, readable code
   - Follow PSR-12 coding standards
   - Add or update tests as needed
   - Update documentation if applicable

4. **Run the test suite**
   ```bash
   composer test
   ```

5. **Run code quality tools**
   ```bash
   composer lint
   composer test:types
   composer test:refactor
   ```

6. **Commit your changes**
   - Use clear, descriptive commit messages
   - Follow conventional commit format when possible
   ```bash
   git commit -m "feat: add new feature"
   git commit -m "fix: resolve issue with likes"
   ```

7. **Push to your fork**
   ```bash
   git push origin feature/your-feature-name
   ```

8. **Create a Pull Request**
   - Provide a clear description of changes
   - Reference any related issues
   - Explain the reasoning behind your changes

## Development Setup

### Prerequisites

- PHP 8.4 or higher
- Composer
- Git

### Installation

```bash
# Clone your fork
git clone https://github.com/your-username/laravel-likeable.git
cd laravel-likeable

# Install dependencies
composer install
```

### Running Tests

```bash
# Run full test suite
composer test

# Run specific test types
composer test:coverage
composer test:types
composer test:type-coverage

# Run linter
composer lint

# Run refactoring checks
composer test:refactor
```

## Coding Standards

### PSR-12

This project follows PSR-12 coding standards. Use Laravel Pint to format code:

```bash
composer lint
```

### Type Safety

All code must be strictly typed:

```php
declare(strict_types=1);

public function like(Model $model): bool
{
    // Implementation
}
```

### Documentation

- Add PHPDoc blocks to public methods
- Keep comments clear and concise
- Update relevant documentation files

```php
/**
 * Like the given model.
 *
 * @param Model $model The model to like
 * @return bool True if successfully liked
 */
public function like(Model $model): bool
{
    // Implementation
}
```

## Testing Requirements

### Coverage

- Maintain minimum 82.5% code coverage
- Add tests for all new features
- Update tests when modifying existing features

### Test Types

Write tests that cover:

- **Feature tests**: End-to-end functionality
- **Unit tests**: Individual methods and classes
- **Integration tests**: Interactions between components

### Example Test

```php
test('user can like a post', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();
    
    $result = $user->like($post);
    
    expect($result)->toBeTrue()
        ->and($post->likesCount())->toBe(1);
});
```

## Documentation

When contributing features:

- Update relevant documentation in `/docs`
- Add code examples
- Keep explanations clear and concise
- Update the README if necessary

## Commit Message Guidelines

Use conventional commits format:

- `feat:` New feature
- `fix:` Bug fix
- `docs:` Documentation changes
- `style:` Code style changes (formatting, etc.)
- `refactor:` Code refactoring
- `test:` Adding or updating tests
- `chore:` Maintenance tasks

Examples:
```
feat: add support for soft delete integration
fix: resolve duplicate like issue
docs: update installation instructions
test: add tests for toggle functionality
```

## Review Process

1. All submissions require review
2. Maintainers may request changes
3. Address feedback promptly
4. Once approved, your PR will be merged

## Release Process

Maintainers handle releases:

1. Version bumping follows semantic versioning
2. Changelog is automatically generated
3. Releases are tagged and published

## Questions?

If you have questions about contributing:

- Open a discussion on GitHub
- Check existing issues and pull requests
- Review the documentation

## License

By contributing, you agree that your contributions will be licensed under the MIT License.

## Recognition

Contributors are recognized in:
- The repository's contributor graph
- Release notes (for significant contributions)
- README credits section

Thank you for contributing to Laravel Likeable!
