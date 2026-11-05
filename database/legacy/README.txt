Old one-off patch scripts, kept only for reference.
A new database does NOT need any of them: schema.sql + seed.sql + roles.sql already include everything.
Do not run these on a database built from the current schema.sql (signup_setup.sql, for example, refers to a
column called "password" that no longer exists).
