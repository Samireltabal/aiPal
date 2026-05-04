# aiPal User Roles & Permissions

aipal supports a multi-user environment where each user can have specific roles and permissions, managed by the system admin. This document clarifies the default user roles, permission scopes, and how to manage user access.

## Default Roles
- **Admin:** Full access to all data and settings; can invite new users and configure system-wide options.
- **User:** Access to personal assistant, notes, tasks, files, and standard integrations. Users cannot modify other users' data.

## Inviting Users
- Admins generate signed invite links in the dashboard.
- Each invite is single-use and grants access to the registration/signup flow.

## Isolated Data
- aiPal ensures user isolation: memory, tasks, and uploaded documents are never shared unless explicitly enabled by the admin.

## Upgrading/Downgrading Roles
- Admins can promote or demote users from the dashboard under **Team Management**.

## Audit Logging
- Sensitive actions (data export/import, deleting users, etc.) are logged for auditing and security.
