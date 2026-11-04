# Fresh installation

The full `employee_evaluation_mvc.sql` file in this directory is a local database
backup and contains application records. It is excluded from Git and must not be
used as a fresh-install seed or made publicly downloadable.

`install_schema.sql` contains the current table and trigger definitions only.
`install_seeds.sql` contains system roles, permissions, role assignments, grading
scales, and rating defaults; it does not contain users, employees, evaluations,
audit records, or credentials.

## Install

1. Use a MySQL/MariaDB account allowed to create a database and write to the
   application's `config` directory.
2. Open `public/setup.php` in a browser and enter the connection details for a
   new, empty database. The wizard refuses to install into a database that
   already contains tables or views.
3. Create the initial administrator account. The wizard applies the application's
   password policy and requires a password change at first login.
4. After successful setup, `config/installed.lock` prevents the wizard from
   running again. Do not remove the lock during normal operation.

The SQL files and local backup are protected by the Apache rules in this
directory. They are read from disk by the wizard and are not served directly.
If installation fails while applying the schema, review the server error log;
the wizard reports the failure and does not create the installed lock.
