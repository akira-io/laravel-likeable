# Roadmap

This document outlines future improvements and features being considered for Laravel Likeable. Items are listed without specific timelines or version targets.

## Core Functionality Enhancements

### Dislike Functionality
Add support for explicit dislikes alongside likes, enabling positive and negative reactions. This would include:
- New `Dislikeable` trait for models
- `dislike()`, `undislike()`, and `toggleDislike()` methods
- `DislikedEvent` and `UnDislikedEvent` events
- Separate dislike counter and relationship methods
- Support for neutral state (neither liked nor disliked)

### Like Types and Categories
Support multiple types of reactions beyond simple likes:
- Love, Celebrate, Support, Insightful (similar to social platforms)
- Custom reaction types configurable per model
- Reaction type filtering and counting
- Migration path for existing simple likes

### Batch Operations
Optimize performance when working with multiple entities:
- Bulk like/unlike operations
- Batch status attachment with fewer queries
- Eager loading optimization for large datasets
- Cache layer for frequently accessed like counts

## Performance and Scalability

### Counter Cache
Reduce query overhead by caching like counts directly on models:
- Automatic counter column updates
- Configurable counter column names
- Migration generator for adding counter columns
- Background job for recalculating existing counts

### Redis Integration
Optional Redis support for high-traffic applications:
- Real-time like count updates
- Sorted sets for trending content
- TTL-based caching strategies
- Fallback to database when Redis unavailable

## Developer Experience

### Query Scopes
Additional Eloquent query scopes for common filtering needs:
- `mostLiked()` - Order by like count
- `likedBy($user)` - Filter to items liked by specific user
- `likedBetween($start, $end)` - Date range filtering
- `withLikeCount()` - Eager load counts efficiently

### API Resources
Built-in JSON API resource transformers:
- Like relationship serialization
- Has-liked status inclusion
- Configurable attribute names
- OpenAPI schema generation

### Notification System
Optional notification integration:
- Notify model owners when liked
- Configurable notification channels
- Throttling to prevent spam
- Customizable notification messages

## Data and Analytics

### Analytics Tracking
Built-in analytics for understanding engagement:
- Like trends over time
- Peak engagement periods
- User engagement metrics
- Export capabilities for external analysis

### Reporting Commands
Artisan commands for insights:
- Most liked content report
- User engagement statistics
- Like activity summary
- Data integrity checks

## Extensions and Integrations

### Middleware Support
HTTP middleware for like-related actions:
- Rate limiting for like operations
- Authentication requirements
- Authorization policies
- Activity logging

### Policy Integration
First-party support for Laravel policies:
- `LikePolicy` examples
- Authorization helpers
- Permission checking methods
- Gate definitions

### Livewire Components
Pre-built Livewire components:
- Like button with real-time updates
- Like counter display
- User avatar list of likers
- Customizable styling

### Broadcast Events
Real-time broadcasting support:
- Pusher/Echo integration
- WebSocket notifications
- Live counter updates
- Presence channels for active likers

## Testing and Development

### Test Helpers
Additional testing utilities:
- Factory methods for likes
- Assertion helpers
- Fake implementations
- Time-based testing tools

### Database Seeding
Seeders for development and testing:
- Realistic like distribution
- Configurable data volume
- User behavior simulation
- Temporal patterns

## Configuration and Customization

### Multiple Like Tables
Support for separating likes by context:
- Different tables per model type
- Partitioning strategies
- Custom table naming conventions
- Migration templates

### Soft Deletes Integration
Enhanced handling of soft-deleted models:
- Option to preserve likes on soft delete
- Restore like relationships on model restoration
- Query scopes excluding soft-deleted likes
- Cleanup commands

### UUID Support Enhancement
Improved UUID handling:
- Automatic UUID detection
- Support for ULID
- Custom ID generator integration
- Migration compatibility

**Next:** [Installation](01-installation.md)
