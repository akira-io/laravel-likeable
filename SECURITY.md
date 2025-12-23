# Security Policy

## Supported Versions

We release patches for security vulnerabilities for the following versions:

| Version | Supported          |
| ------- | ------------------ |
| 0.2.x   | :white_check_mark: |
| < 0.2   | :x:                |

## Reporting a Vulnerability

We take the security of Laravel Likeable seriously. If you discover a security vulnerability, please follow these steps:

### Do Not

- **Do not** open a public GitHub issue
- **Do not** disclose the vulnerability publicly until it has been addressed
- **Do not** exploit the vulnerability beyond what is necessary to demonstrate it

### Do

1. **Email the maintainers** at [kidiatoliny@akira-io.com](mailto:kidiatoliny@akira-io.com)
2. **Include detailed information**:
   - Description of the vulnerability
   - Steps to reproduce
   - Potential impact
   - Suggested fix (if any)
   - Your contact information

3. **Allow time for response**:
   - We will acknowledge receipt within 48 hours
   - We will provide a detailed response within 7 days
   - We will keep you informed about the progress

### What to Expect

#### Initial Response (48 hours)

We will acknowledge your report and begin investigating the issue.

#### Assessment (7 days)

We will:
- Confirm the vulnerability
- Assess the severity and impact
- Develop a fix
- Determine the release timeline

#### Resolution

Once the vulnerability is confirmed:
- We will develop and test a patch
- We will prepare a security advisory
- We will coordinate the release with you

#### Disclosure

After the fix is released:
- We will publish a security advisory
- We will credit you for the discovery (unless you prefer to remain anonymous)
- We will update the changelog

## Security Measures

### Package Security

Laravel Likeable implements several security measures:

- **Type Safety**: Strict typing prevents type-related vulnerabilities
- **SQL Injection Protection**: Uses Eloquent ORM and prepared statements
- **Mass Assignment Protection**: Explicit `$fillable` attributes
- **Input Validation**: Type validation using the `type-guard` package

### Best Practices for Users

When using Laravel Likeable:

1. **Keep dependencies updated**
   ```bash
   composer update akira/laravel-likeable
   ```

2. **Use HTTPS** for all requests involving like actions

3. **Implement rate limiting** to prevent abuse
   ```php
   Route::post('/posts/{post}/like', [LikeController::class, 'store'])
       ->middleware('throttle:60,1');
   ```

4. **Validate authorization** before allowing likes
   ```php
   $this->authorize('like', $post);
   ```

5. **Sanitize user input** if displaying like-related data
   ```blade
   {{ $post->likers()->pluck('name')->implode(', ') }}
   ```

6. **Use CSRF protection** on like endpoints (enabled by default in Laravel)

## Known Security Considerations

### User Authentication

The package relies on Laravel's authentication. Ensure:
- Authentication is properly configured
- User sessions are secure
- CSRF protection is enabled

### Database Security

- Use environment variables for database credentials
- Restrict database user permissions
- Enable database connection encryption

### Authorization

The package does not include built-in authorization. Implement authorization policies:

```php
// app/Policies/PostPolicy.php
public function like(User $user, Post $post): bool
{
    // Your authorization logic
    return !$post->user_id === $user->id; // Can't like own posts
}
```

### Rate Limiting

Implement rate limiting to prevent:
- Spam liking
- API abuse
- Denial of service attacks

```php
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('likes', function (Request $request) {
    return Limit::perMinute(10)->by($request->user()?->id);
});
```

## Vulnerability Disclosure Policy

We follow responsible disclosure practices:

1. **Private disclosure**: Report privately to maintainers
2. **Fix development**: We develop and test a fix
3. **Coordinated release**: We coordinate release timing with reporter
4. **Public disclosure**: We publish advisory after fix is available

## Security Updates

Subscribe to security updates:
- Watch the GitHub repository
- Check release notes for security fixes
- Review the CHANGELOG for security-related changes

## Security Checklist for Deployments

Before deploying applications using Laravel Likeable:

- [ ] Latest package version installed
- [ ] Authentication properly configured
- [ ] Authorization policies implemented
- [ ] Rate limiting enabled on like endpoints
- [ ] CSRF protection enabled
- [ ] Database credentials secured
- [ ] HTTPS enabled in production
- [ ] Error messages don't expose sensitive information
- [ ] Logs don't contain sensitive data

## Contact

For security concerns:
- **Email**: kidiatoliny@akira-io.com
- **GitHub**: Do not use public issues for security reports

## Recognition

We appreciate security researchers who help keep Laravel Likeable secure. Responsible disclosure will be acknowledged in:
- Security advisories
- Release notes
- Repository credits (unless anonymity is preferred)

## Additional Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Laravel Security Documentation](https://laravel.com/docs/security)
- [PHP Security Guide](https://www.php.net/manual/en/security.php)

Thank you for helping keep Laravel Likeable secure!
