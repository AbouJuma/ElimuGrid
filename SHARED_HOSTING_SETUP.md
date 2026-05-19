# Shared Hosting Tenant Architecture - Setup Guide

## Overview
This document explains the shared hosting compatible tenant architecture implemented for elimuGrid SaaS application.

## Problem Solved
- **Original Issue**: Shared hosting environments restrict `CREATE DATABASE` permissions, preventing the creation of separate databases for each school/tenant.
- **Solution**: Use table prefixes (`s{id}_`) instead of separate databases to achieve tenant isolation.

## Architecture Changes

### 1. Table Prefix System
- Each school gets tables with prefix: `s{school_id}_table_name`
- Example: School ID 1 has tables `s1_users`, `s1_classes`, etc.
- MySQL 64-character index name limit handled with shorter prefix format

### 2. Updated Files

#### `app/Services/SharedHostingTenantService.php`
- **switchToTenant($schoolId)**: Switches database context using table prefixes
- **switchToMain()**: Returns to main database context (no prefix)
- **createTenantTables($schoolId)**: Creates tenant tables using migrations or fallback
- **dropTenantTables($schoolId)**: Removes all tenant tables for school deletion
- **tenantExists($schoolId)**: Checks if tenant tables exist

#### `app/Http/Controllers/SchoolController.php`
- **store()**: Updated to use prefix-based tables instead of separate databases
- **update()**: Uses tenant switching for school updates
- **trash()**: Drops tenant tables instead of databases

#### `app/Http/Middleware/SharedHostingTenantMiddleware.php`
- Automatically switches to tenant context for authenticated school users
- Skips admin routes and guest users

### 3. Database Configuration
- Uses single database connection with dynamic prefix switching
- No separate database connections required
- Compatible with shared hosting limitations

## Benefits

### ✅ Shared Hosting Compatible
- No `CREATE DATABASE` permissions required
- Works on cPanel, Plesk, and other shared hosting platforms
- Uses standard MySQL features available on shared hosting

### ✅ Tenant Isolation
- Each school has completely separate tables
- No data leakage between tenants
- Proper security boundaries maintained

### ✅ Performance
- Single database connection reduces overhead
- Table prefix switching is efficient
- No cross-database queries needed

### ✅ Scalability
- Add unlimited schools without database limits
- Easy to backup and manage
- Simple tenant deletion

## Usage Examples

### Creating a New School
```php
// School creation automatically handles tenant setup
$school = $this->schoolsRepository->create($schoolData);
SharedHostingTenantService::createTenantTables($school->id);
```

### Working with Tenant Data
```php
// Switch to tenant context
SharedHostingTenantService::switchToTenant($schoolId);

// Work with tenant data
$users = DB::table('users')->get(); // Uses s{id}_users table

// Switch back to main
SharedHostingTenantService::switchToMain();
```

### Manual Tenant Management
```php
// Check if tenant exists
$exists = SharedHostingTenantService::tenantExists($schoolId);

// Drop tenant tables
SharedHostingTenantService::dropTenantTables($schoolId);
```

## Migration Scripts

### `migrate_to_shared_hosting.php`
- Migrates existing schools to prefix-based architecture
- Creates tenant tables for all existing schools
- Updates school records with new database_name format

### `create_tenant_test.php`
- Tests tenant switching functionality
- Verifies table creation and data isolation
- Validates shared hosting compatibility

## Testing Results
✅ Tenant switching works correctly  
✅ Table prefix isolation maintained  
✅ User creation and retrieval successful  
✅ Main database access preserved  
✅ Tenant isolation verified  
✅ No CREATE DATABASE permissions required  

## Deployment Notes

1. **Shared Hosting**: Ready for immediate deployment on shared hosting
2. **VPS/Dedicated**: Also works with full server access
3. **Backup Strategy**: Single database backup includes all tenants
4. **Performance**: Monitor table count per tenant for optimal performance

## Maintenance

### Adding New Tables
- Add migrations to `database/migrations/schools/`
- Run `SharedHostingTenantService::createTenantTables($schoolId)` for existing schools

### Tenant Deletion
- Use `SharedHostingTenantService::dropTenantTables($schoolId)`
- Automatically handles all prefixed tables

### Troubleshooting
- Check prefix length if index errors occur (MySQL 64-char limit)
- Verify tenant switching in middleware logs
- Use test scripts to validate functionality

## Security Considerations
- Each tenant has completely isolated data
- No cross-tenant data access possible
- Proper SQL injection protection maintained
- Authentication and authorization preserved

This architecture successfully eliminates shared hosting limitations while maintaining full multi-tenant functionality.
