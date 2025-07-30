# Laravel 12+ ERP Development Guidelines and Best Practices

## General Code Instructions

- **Comments:**  
  Do **not** generate code comments above methods or blocks that are obvious in purpose. Generate comments **only** to explain *why* specific code or logic is implemented in a certain way (intent and reasoning), **not** what the code does if that's apparent from the implementation.

- **Code Changes:**  
  When changing existing code, never comment out old code unless explicitly instructed. Assume the Git history preserves older versions, promoting clean and readable diffs.

- **Single Responsibility Principle (SRP):**  
  Apply SRP consistently: keep controllers lightweight and delegate business and complex logic to service classes or custom classes.

- **Mass Assignment Protection:**  
  Use `$fillable` or `$guarded` properties rigorously in models to avoid mass assignment vulnerabilities.

---

## Laravel 12+, Laravel 11+ Skeleton Structure Guidelines

### Artisan Commands for File Generation

- Always use Laravel Artisan commands to generate files and folders, e.g.:  
  - `php artisan make:model ModelName -a` (creates model, migration, factory, seeder, and policy)  
  - `php artisan make:view viewname`  
  - `php artisan make:factory FactoryName` (if needed; `-a` flag for model also generates factory)  

- Do **not** use manual `mkdir` or file creation commands.

### Service Providers

- Use only the existing `AppServiceProvider` unless there is a compelling reason to create new service providers.
- If a new service provider is necessary, register it in `bootstrap/providers.php` (do **not** register in `config/app.php`).

### Event Listeners

- Utilize Laravel 11+ event auto-discovery by type-hinting event listeners. No manual registration is required.

### Console Scheduler

- Place all scheduled commands and tasks in `routes/console.php`.  
- The old `app/Console/Kernel.php` file no longer exists from Laravel 11+ onwards.

### Middleware

- Prefer registering middleware by their class names directly in route definitions.
- If an alias is necessary, register the middleware alias in `bootstrap/app.php`, **not** in `app/Http/Kernel.php`.

### Blade Views

- Generate Blade view template files using `php artisan make:view`.
- Use **Tailwind CSS** by default for all new Blade templates, as Tailwind is pre-configured with Vite since Laravel 11.
- Avoid Bootstrap unless explicitly requested.

### Model Factories

- Use the `fake()` helper instead of the deprecated `$this->faker` in factories.

### Policies

- Rely on Laravel's automatic policy discovery feature. No need for manual registration in service providers.

### Migrations

- For pivot tables, always use an alphabetical naming convention for the migration filename and class.  
  For example:  
  `create_project_role_table` instead of `create_role_project_table`.
- Ensure `down()` methods in migrations are complete and safe to allow proper rollback.

---

## Livewire Usage (Latest Standard for Laravel 12+)

- Utilize **Livewire v3+** best practices including:  
  - Use root elements in components to avoid update issues.  
  - Pass only primitive types (strings, ints, arrays) to public properties; do not pass large Eloquent models directly.  
  - Use Livewire's **form objects** abstraction for better maintainability.  
  - Prefer event listeners for updates instead of polling for better performance.  
  - Use loading states to improve UX and prevent user actions during processing.  
  - Use `wire:model.lazy` or deferred binding to reduce excessive requests.  
  - Leverage lazy loading of components to improve page responsiveness.

- Use `php artisan livewire:make ComponentName` to generate components.

- Validate input using Form Request classes when possible, integrating validation rules into Livewire components.

- Avoid nesting Livewire components deeper than one level to prevent DOM diffing issues; prefer Blade components for deeper nesting.

### ERP-Specific Livewire Components

- **Real-time notifications:** Use Livewire events with Laravel Echo for multi-user scenarios
- **File upload components:** Implement secure file handling with validation
- **Data tables:** Use server-side processing for large datasets with pagination
- **Print/PDF generation:** Integrate with libraries like DomPDF or Snappy
- **Modal components:** Create reusable modals for CRUD operations

---

## spatie/laravel-query-builder Integration

- Use **spatie/laravel-query-builder** for safe and powerful CRUD data filtering, sorting, and inclusion:  
  - Expose filters matching database columns explicitly in controllers or repositories.  
  - Use the package's filter classes and custom filters where needed to dynamically build queries based on request parameters.  
  - Always validate and sanitize query parameters to prevent injection or unintended data exposure.  
  - Example usage snippet in a repository or controller method:  
    ```php
    use Spatie\QueryBuilder\QueryBuilder;
    use Spatie\QueryBuilder\AllowedFilter;

    $users = QueryBuilder::for(User::class)
        ->allowedFilters([AllowedFilter::exact('status'), 'name', 'email'])
        ->allowedSorts('created_at', 'name')
        ->paginate(15);
    ```
- Combine this with repository pattern to separate query logic from controllers for cleaner code.

---

## Laravel Repository Pattern Usage

### Repository Structure (Laravel 12+ Best Practice)
```
app/
├── Contracts/
│   └── Repositories/
│       └── UserRepositoryContract.php
├── Repositories/
│   ├── BaseRepository.php
│   └── UserRepository.php
└── Services/
    └── UserService.php
```

### Implementation Guidelines

**Base Repository Pattern:**
```php
// app/Contracts/Repositories/BaseRepositoryContract.php
interface BaseRepositoryContract
{
    public function all(array $columns = ['*']): Collection;
    public function find(int $id): ?Model;
    public function create(array $data): Model;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
    public function paginate(int $perPage = 15): LengthAwarePaginator;
    public function findWhere(array $criteria): Collection;
}

// app/Repositories/BaseRepository.php
abstract class BaseRepository implements BaseRepositoryContract
{
    protected Model $model;
    
    public function __construct()
    {
        $this->model = $this->getModel();
    }
    
    abstract protected function getModel(): string;
    
    // Implement base methods with spatie/query-builder integration
}
```

**Specific Repository Implementation:**
```php
// app/Contracts/Repositories/UserRepositoryContract.php
interface UserRepositoryContract extends BaseRepositoryContract
{
    public function findByEmail(string $email): ?User;
    public function getActiveUsers(): Collection;
}

// app/Repositories/UserRepository.php
class UserRepository extends BaseRepository implements UserRepositoryContract
{
    protected function getModel(): string
    {
        return User::class;
    }
    
    public function findByEmail(string $email): ?User
    {
        return $this->model::where('email', $email)->first();
    }
    
    public function getActiveUsers(): Collection
    {
        return QueryBuilder::for(User::class)
            ->allowedFilters(['name', 'email', 'status'])
            ->allowedSorts('created_at', 'name')
            ->where('status', 'active')
            ->get();
    }
}
```

**Service Layer Integration:**
```php
// app/Services/UserService.php
class UserService
{
    public function __construct(
        private UserRepositoryContract $userRepository
    ) {}
    
    public function createUser(array $data): User
    {
        // Business logic validation
        $validatedData = $this->validateUserData($data);
        
        // Repository call
        return $this->userRepository->create($validatedData);
    }
}
```

**Dependency Injection Binding:**
```php
// bootstrap/providers.php or AppServiceProvider
$this->app->bind(
    UserRepositoryContract::class,
    UserRepository::class
);
```

**Controller Usage:**
```php
class UserController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}
    
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->createUser($request->validated());
        return response()->json($user, 201);
    }
}
```

---

## Laravel 12.8+ Eager Loading & Performance

### Automatic Relation Loading (Laravel 12.8+)
- **Auto-loading:** Use `withRelationshipAutoloading()` for dynamic eager loading
- **Global Auto-loading:** Enable with `Model::automaticallyEagerLoadRelationships()`
- **Performance:** Prevents N+1 queries without manual specification

```php
// Automatic loading - detects and loads relations as accessed
$projects = Project::all()->withRelationshipAutoloading();

// Manual chaining - traditional approach
$projects->load([
    'client.owner.details',
    'client.customPropertyValues', 
    'posts.authors.articles.likes'
]);

// Global auto-loading for all models
Model::automaticallyEagerLoadRelationships();
```

### Parent Model Hydration (Laravel 12.8+)
- **Chaperone method:** Auto-hydrates parent models on children
- **Prevents N+1:** Eliminates queries when accessing parent from child

```php
// Define with chaperone for auto-hydration
public function comments(): HasMany
{
    return $this->hasMany(Comment::class)->chaperone();
}

// Runtime chaperone
$posts = Post::with([
    'comments' => fn($q) => $q->chaperone()
])->get();
```

### Code Formatting
- **Laravel Pint:** Use `./vendor/bin/pint` for automatic code formatting
- **Pre-commit Hook:** Run Pint before every commit
- **CI/CD Integration:** Include Pint checks in deployment pipeline

### Background Processing & Queue Management
- **Heavy Operations:** Queue all database-intensive operations (imports, exports, reports)
- **Chunk Processing:** Process large datasets in chunks to prevent memory issues
- **Queue Workers:** Use Redis queues with Supervisor for reliability
- **Job Batching:** Use Laravel's job batching for related operations

**Queue Implementation Examples:**
```php
// Large data import
dispatch(new ProcessLargeImportJob($file))->onQueue('imports');

// Chunked processing
User::chunk(1000, function ($users) {
    ProcessUsersJob::dispatch($users)->onQueue('processing');
});

// Batch jobs for related operations
Bus::batch([
    new GenerateReportJob($data),
    new SendNotificationJob($users),
    new UpdateCacheJob(),
])->dispatch();
```

## Laravel 12+ Validation & Form Requests

### Form Request Validation
- **Generate Form Requests:** Use `php artisan make:request` for complex validation
- **Authorization Logic:** Include authorization checks in Form Request
- **Custom Messages:** Define custom validation messages
- **Rule::anyOf():** Validate against multiple rule sets (Laravel 12+)

```php
// Generate Form Request
php artisan make:request StoreUserRequest

// Form Request Implementation
class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create-users');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users'],
            'role' => Rule::anyOf([
                Rule::in(['admin', 'manager']),
                Rule::exists('custom_roles', 'name')
            ]),
            'department_id' => ['required', 'exists:departments,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'This email is already registered.',
            'role.anyof' => 'Invalid role selection.',
        ];
    }

    // Custom validation data preparation
    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug($this->name),
        ]);
    }
}

// Controller usage
public function store(StoreUserRequest $request): JsonResponse
{
    $user = $this->userService->create($request->validated());
    return response()->json($user, 201);
}
```

### Enhanced JSON & Conditional Validation
```php
// Complex JSON validation
'settings' => ['required', 'json', 'min:10'],
'preferences.*.type' => ['required', 'in:email,sms,push'],

// Conditional validation with Rule::when()
'discount' => Rule::when($this->input('type') === 'premium', [
    'required', 'numeric', 'min:10'
], ['nullable']),
```
- **fromJson:** Create collections from JSON strings directly
- **Force Create Many:** Bulk operations with `forceCreateMany()` and `forceCreateManyQuietly()`

```php
// Collection from JSON
$collection = Collection::fromJson($jsonString, flags: JSON_THROW_ON_ERROR);

// Bulk force creation
$post->comments()->forceCreateMany($commentsData);
$post->comments()->forceCreateManyQuietly($commentsData);
```

## Code Quality & Performance

### Database Optimization
- **Indexing Strategy:** Create indexes for frequently queried columns, foreign keys, and composite indexes for multi-column searches
- **Query Optimization:** 
  - Use `with()` for eager loading to prevent N+1 queries
  - Implement query scopes for reusable query logic
  - Use `select()` to fetch only required columns for large datasets
- **Database Transactions:** Wrap complex operations in database transactions for data consistency
- **Chunked Processing:** Use `chunk()` for large dataset operations to prevent memory exhaustion

### Queue-Based Processing
- **Heavy Operations:** Queue database-intensive operations (imports, exports, reports, bulk updates)
- **Batch Processing:** Use Laravel's job batching for related operations
- **Background Jobs:** Process time-consuming tasks asynchronously

**Queue Implementation:**
```php
// Large data operations
dispatch(new ProcessLargeDatasetJob($data))->onQueue('heavy');

// Chunked processing to prevent memory issues
Model::chunk(1000, function ($records) {
    ProcessChunkJob::dispatch($records)->onQueue('processing');
});

// Batch jobs for related operations
Bus::batch([
    new GenerateReportJob($filters),
    new SendReportEmailJob($recipients),
    new CleanupTempFilesJob(),
])->onQueue('reports')->dispatch();

// File imports with progress tracking
ImportFileJob::dispatch($file)
    ->onQueue('imports')
    ->withChain([
        new NotifyImportCompleteJob($userId),
        new UpdateDashboardStatsJob(),
    ]);
```

### Caching Strategy
- **Query Caching:** Cache expensive queries using `cache()->remember()`
- **Model Caching:** Implement model-level caching for frequently accessed data
- **Session Storage:** Use Redis for session storage in production
- **Configuration Caching:** Run `php artisan config:cache` and `php artisan route:cache` in production

### Background Processing
- **Queue Configuration:** Use Redis queues with multiple queue names (default, heavy, imports, exports, reports)
- **Queue Workers:** Monitor with Supervisor in production
- **Job Retries:** Set appropriate retry attempts and backoff strategies
- **Failed Jobs:** Implement proper failed job handling and alerting

**Queue Configuration:**
```php
// config/queue.php - Redis setup
'redis' => [
    'driver' => 'redis',
    'connection' => 'default',
    'queue' => env('REDIS_QUEUE', 'default'),
    'retry_after' => 90,
    'block_for' => null,
],

// Different queues for different operations
'imports' => ['heavy', 'imports'],
'exports' => ['heavy', 'exports'], 
'reports' => ['heavy', 'reports'],
'default' => ['default'],
```

---

## Security & Permissions

### Security & Authentication
- **Authentication:** Use Laravel Sanctum for SPA authentication when APIs are needed
- **Authorization:** Implement RBAC using `spatie/laravel-permission`
- **Route Protection:** Protect routes with middleware and policies

### Data Security
- **Input Validation:** Use Form Request classes for comprehensive validation
- **SQL Injection Prevention:** Always use parameterized queries and Eloquent ORM
- **XSS Protection:** Escape output in Blade templates (automatic with `{{ }}`)
- **CSRF Protection:** Ensure CSRF tokens are included in forms
- **File Upload Security:** Validate file types, sizes, and store outside public directory

### Audit & Logging
- **Activity Logging:** Use `spatie/laravel-activitylog` for tracking user actions
- **Error Logging:** Configure comprehensive logging in `config/logging.php`
- **Security Events:** Log authentication attempts, permission changes, and sensitive operations

---

## ERP-Specific Architecture

### Module Structure
```
app/
├── Modules/
│   ├── Accounting/
│   ├── Inventory/
│   ├── HumanResources/
│   ├── Sales/
│   └── Purchasing/
```

### Multi-Tenancy (if required)
- **Database Strategy:** Choose between single database, multiple databases, or schema-based tenancy
- **Tenant Resolution:** Implement tenant identification via subdomain or domain
- **Data Isolation:** Ensure complete data separation between tenants

### Inter-Module Communication
- **Events & Listeners:** Use Laravel events for loose coupling between modules
- **Service Layer:** Create service classes for cross-module operations
- **Shared Resources:** Place common utilities in `app/Support/` directory

**Code Quality Workflow:**
```bash
# Before committing
./vendor/bin/pint
./vendor/bin/pest

# Git hooks (optional)
# .git/hooks/pre-commit
#!/bin/sh
./vendor/bin/pint --test
./vendor/bin/pest --parallel
```

### Data Import/Export with Queue Processing
- **Excel Integration:** Use `maatwebsite/excel` with queued processing
- **CSV Processing:** Implement chunked processing for large files  
- **Progress Tracking:** Use job batching with progress updates

```php
// Queued Excel import
Excel::queueImport(new CustomersImport, 'customers.xlsx', 'uploads', 'imports');

// Chunked CSV processing  
Bus::batch(
    collect($csvChunks)->map(fn($chunk) => new ProcessCsvChunkJob($chunk))
)->onQueue('imports')->dispatch();
```

---

## Required ERP Packages

```bash
# Core ERP functionality
composer require spatie/laravel-permission
composer require spatie/laravel-query-builder
composer require spatie/laravel-activitylog
composer require maatwebsite/excel
composer require barryvdh/laravel-dompdf

# Infrastructure
composer require predis/predis
composer require spatie/laravel-backup
composer require league/flysystem-aws-s3-v3

# Development
composer require --dev pestphp/pest
composer require --dev pestphp/pest-plugin-laravel
composer require --dev pestphp/pest-plugin-livewire
composer require --dev laravel/pint
composer require --dev laravel/telescope
composer require --dev nunomaduro/collision
```

---

## File Management

### File Storage
- **Disk Configuration:** Configure separate disks for different file types
- **File Validation:** Validate file types, sizes, and content
- **Storage Security:** Store sensitive files outside public directory
- **File Organization:** Organize files by module, date, or entity type

### Document Management
- **Version Control:** Implement document versioning for important files
- **Access Control:** Restrict file access based on user permissions
- **Metadata Storage:** Store file metadata in database for search functionality

---

## Testing Strategy

### Test Structure
```
tests/
├── Feature/
│   ├── Auth/
│   ├── Modules/
│   ├── Livewire/
│   ├── Authorization/
│   └── Validation/
├── Unit/
│   ├── Models/
│   ├── Services/
│   └── Repositories/
```

---

## CRUD Test Generation Requirements

**Mandatory for Every CRUD Resource:**
- Repository unit tests (all CRUD methods)
- Service layer unit tests with mocking
- Livewire component feature tests
- Authorization tests with roles/permissions
- Validation tests for all form requests
- Feature tests for complete workflows

**Pest Configuration:**
```php
// tests/Pest.php
<?php

uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class,
)->in('Feature');

uses(Tests\TestCase::class)->in('Unit');

expect()->extend('toBeModel', function (string $model) {
    return $this->toBeInstanceOf($model);
});
```

### Testing Guidelines
- **Feature Tests:** Test complete workflows and business processes
- **Unit Tests:** Test repositories, services, and models in isolation  
- **Livewire Tests:** Test component interactions and data binding
- **Database Testing:** Use database transactions for test isolation
- **CRUD Testing:** Generate Pest tests for all CRUD operations automatically

---

## Error Handling & Monitoring

### Error Management
- **Exception Handling:** Create custom exception classes for business logic errors
- **User-Friendly Errors:** Display meaningful error messages to users
- **Error Reporting:** Configure error reporting services (Sentry, Flare)

### Monitoring
- **Performance Monitoring:** Monitor application performance and database queries
- **Health Checks:** Implement health check endpoints for system monitoring
- **Log Analysis:** Set up log aggregation and analysis tools

---

## Deployment & Production

### Environment Configuration
- **Environment Variables:** Use `.env` files for environment-specific configuration
- **Configuration Caching:** Cache configuration in production for better performance
- **Asset Optimization:** Minify and version assets using Laravel Mix/Vite

### Production Deployment
- Run `./vendor/bin/pint` before deployment
- Configure queue workers with proper timeout and memory limits
- Set up queue monitoring and failed job alerts
- Use `php artisan optimize` for performance

---

## Project-Specific Guidelines

- Project is based on **Laravel 12** using the **Laravel Livewire starter kit**.
- Use native Laravel authentication scaffolding included with the Livewire starter kit.
- Keep these packages updated to versions compatible with Laravel 12:  
  - `spatie/laravel-permission`  
  - `spatie/laravel-query-builder`  
  - `spatie/laravel-activitylog`
- Leverage and maintain compatibility with the latest Laravel 12 features and improvements.

---

## Additional Best Practices

- Follow the **PSR-12 coding standard** and Laravel's official style guide for formatting and naming conventions.
- Write clean, maintainable, and testable code.
- Prefer dependency injection for class dependencies.
- Avoid hardcoding configuration or environment-specific values; use configuration files and `.env`.
- Validate all requests properly, preferably with Form Request classes.
- Cover critical and complex logic with automated tests (feature and unit tests).
- Use Laravel's typed properties and return types where possible to improve clarity and static analysis.
- Implement proper logging for debugging and monitoring.
- Use database seeders for consistent development and testing data.
- Implement proper backup strategies for production data.
- Monitor application performance and optimize bottlenecks.
- Keep dependencies updated and secure.
- Document complex business logic and architectural decisions.
