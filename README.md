pop-kettle
==========

[![Build Status](https://github.com/popphp/pop-kettle/workflows/phpunit/badge.svg)](https://github.com/popphp/pop-kettle/actions)
[![Coverage Status](http://cc.popphp.org/coverage.php?comp=pop-kettle)](http://cc.popphp.org/pop-kettle/)

[![Join the chat at https://discord.gg/TZjgT74U7E](https://media.popphp.org/img/discord.svg)](https://discord.gg/TZjgT74U7E)

* [Overview](#overview)
* [Install](#install)
* [Initializing an Application](#initializing-an-application)
    + [Application Status](#application-status)
* [Kettle Include](#kettle-include)
* [Managing the Database](#managing-the-database)
    + [Seeding the Database](#seeding-the-database)
    + [Database Migrations](#database-migrations)
    + [Migration State Storage](#migration-state-storage)
    + [Queue Commands](#queue-commands)
* [Creating Application Files](#creating-application-files)
    + [Data Model](#data-model)
* [Creating Custom Commands](#creating-custom-commands)
    + [Kettle Commands](#kettle-commands)
    + [Application Console Scripts](#application-console-scripts)
* [Running the Web Server](#running-the-web-server)
* [Getting Help and Version](#getting-help-and-version)
* [Accessing the Application](#accessing-the-application)
* [Shell Completion](#shell-completion)
* [Using on Windows](#using-on-Windows)

Overview
--------

`pop-kettle` is a CLI-helper application for the Pop PHP Framework that allows
a user to quickly build the scaffolding for an application. It is included with
the Pop PHP Framework as the command `kettle` within the main project directory.

[Top](#pop-kettle)

Install
-------

The `pop-kettle` component comes automatically installed when you install the full Pop PHP
Framework. You should see the `kettle` script in the main project directory. However, if
that is not the case, and you need to install it manually, you can place a copy of the `kettle`
script from the `vendor/popphp/pop-kettle/kettle` location in the main project folder
(adjacent to the `vendor` folder):

```bash
$ cp vendor/popphp/pop-kettle/kettle .
```
Once you've copied the script over, you have to change the reference to the script's
config file from:

```php
    $config = include __DIR__ . '/config/app.console.php';
```

to

```php
    $config = include __DIR__ . '/vendor/popphp/pop-kettle/config/app.console.php';
```

since that config file (which just returns the `routes` array) lives in the `pop-kettle`
package, not in your project's own `config` folder, and make sure the newly copied
`kettle` script is set to execute (755)

```bash
$ chmod 755 kettle
```

[Top](#pop-kettle)

Initializing an Application
---------------------------

By running the following command, you can set up the basic files and folders
required to run an application:

```bash
$ ./kettle app:init [--web] [--api] [--cli] [<namespace>]
```

The `<namespace>` parameter is the namespace of your application - it defaults to `MyApp`.
The optional parameters of `--web`, `--api`, and `--cli` will create the related files
and folders to run the application as a normal web application, an API-driven web
application, a CLI-driven console application or any combination thereof. If none of
`--web`, `--api`, or `--cli` are passed, `--web` is assumed. The default route for the
web application or the API application is `/`. However, if both are initialized, then
the default route for the API application becomes `/api`. The web application will
deliver a placeholder HTML page and the API application will deliver a placeholder
JSON response.

If `--web` and/or `--api` are used, the front controller will be located in
`public/index.php` (there is no `public` folder, and therefore no `public/index.php`,
for a `--cli`-only install). If `--cli` is used, you'll be prompted to optionally
initialize a stand-alone CLI application as well; if you accept, its main script will
be located at `script/myapp`, renamed to the lowercased \<namespace\> value (backslashes
in a multi-segment namespace become hyphens, e.g. `My\App` &rarr; `script/my-app`). See
[Application Console Scripts](#application-console-scripts) below for more on that
stand-alone script versus registering one-off commands directly with `kettle`.

Along the way you'll also be prompted for an app name, environment, and (for `--web`/
`--api`) URL, and asked whether you'd like to configure a database. If you accept, the
application files and folders are copied over first, and then you'll immediately be
walked through the database adapter/connection prompts to create the database
configuration file. See [Managing the Database](#managing-the-database) below for the
list of supported database adapters and what you'll be prompted for.

### Application Status

You can view and manage the status of the application with the following commands outlined below.

#### Check the current environment:

The environment is set in the `.env` file under the `APP_ENV` variable. Options available are:

- `local`
- `dev`
- `testing`
- `staging`
- `production` (or `prod`)

```bash
$ ./kettle app:env
```

#### Check (or change) the current status:

The status of the application can either be "live" or in "maintenance mode". The value is set
in the `.env` file under the `MAINTENANCE_MODE` variable (`true` or `false`).

```bash
$ ./kettle app:status
```

To put the application into maintenance mode, where it's not accessible, use the following command:

```bash
$ ./kettle app:down
```

You can generate a "secret" key to allow a select set of users to view the application while still in
maintenance mode:

```bash
$ ./kettle app:down --secret
```

When the command finishes, it will output the auto-generated secret:

```text
    The secret is SECRET_STRING
```

You can also provide your own secret:

```bash
$ ./kettle app:down --secret=MY_SECRET_STRING
```

Use that string one time in the browser as a URL query parameter to view the application while it is
still in maintenance mode. It will store in the browser's cookies so subsequent requests will be valid:

```text
http://localhost:8000/?secret=SECRET_STRING
```

To take the application out of maintenance mode and make it live again, use the following command:

```bash
$ ./kettle app:up
```

[Top](#pop-kettle)

Kettle Include
--------------

You should see a file `kettle.inc.php` next to the main `kettle` script. This serves
as a configuration file for anything additional that needs to be wired up for your
application to work with kettle. The file is included right after the `$autoloader` is
created and the routes config is loaded, but before the `Pop\Kettle\Application` object
itself is built, so you will have direct access to `$autoloader` and `$config` (and can,
for example, add to `$config['routes']`). In this file you can add any additional
runtime requirements, configurations or routes.

By default, when you initialize an application, `kettle` is made aware of your application
and its namespace by automatically adding this line to the `kettle.inc.php` file:

```php
$autoloader->addPsr4('MyApp\\', __DIR__ . '/app/src');
```

**Note:** If the `kettle.inc.php` file isn't available, you can copy the blank
`kettle.inc.orig.php` template from the `vendor/popphp/pop-kettle` package folder to
the main project folder (adjacent to the `vendor` folder) and rename it to
`kettle.inc.php` — this is the same file `app:init` copies into place automatically.

[Top](#pop-kettle)

Managing the Database
---------------------

Once the application is initialized, you can manage the database, or multiple databases,
by using the `db` and `migrate` commands. If you don't pass anything in the optional
`[<database>]` parameter, it will default to the `default` database. Passing `all` in
place of `<database>` (where supported above) runs the command against every database
that has a folder under `/database/migrations`.

Both `db:config` and `db:install` (which runs `db:config` followed by `db:test` and
`db:seed`) will present a numbered list of the database adapters available on your PHP
install to choose from — typically some combination of PDO (MySQL, PostgreSQL, SQLite)
and the native `mysqli`/`sqlite` adapters, depending on which PHP extensions are
enabled. If you choose a MySQL or PostgreSQL adapter, you'll be prompted for the DB
name, user, password, and host (defaults to `localhost`), and the connection is tested
before the config file is written — you'll be re-prompted on failure. If you choose the
SQLite adapter, you'll only be prompted for a DB name, and a corresponding `.sqlite`
file is created under `/database`.

```bash
./kettle db:install [<database>]                    Install the database (Runs the config, test and seed commands)
./kettle db:config [<database>]                     Configure the database
./kettle db:test [<database>]                       Test the database connection
./kettle db:create-seed <seed> [<database>]         Create database seed class
./kettle db:seed [<database>]                       Seed the database with data
./kettle db:export [<database>]                     Export the database to a file (MySQL only)
./kettle db:import <file> [<database>]              Import the database from a file (MySQL only)
./kettle db:reset [<database>]                      Reset the database with original seed data
./kettle db:clear [<database>]                      Clear the database of all data

./kettle migrate:create <class> [<database>]        Create new database migration class
./kettle migrate:run [<steps>] [<database>]         Perform forward database migration
./kettle migrate:rollback [<steps>] [<database>]    Perform backward database migration
./kettle migrate:point [<id>] [<database>]          Point to specific migration, w/o running (.current file only)
./kettle migrate:reset [<database>]                 Perform complete rollback of the database
```

### Seeding the Database
 
You can seed the database with data in one of two ways. You can either utilize a
SQL file with the extension `.sql` in the `/database/seeds/<database>` folder, or you
can write a seeder class using PHP. To create a seeder class, you can run:

```bash
$ ./kettle db:create-seed <seed> [<database>]
```

Where the `<seed>` is the base class name of the seeder class that will be created.
The template seeder class will be copied to the `/database/seeds/<database>` folder:

```php
<?php

use Pop\Db\Adapter\AbstractAdapter;
use Pop\Db\Sql\Seeder\AbstractSeeder;

class MyFirstSeeder extends AbstractSeeder
{

    public function run(AbstractAdapter $db): void
    {
        
    }

}
```

From there, you can fill in the `run()` method in the seeder class with the SQL you need to seed your data:

```php
<?php

use Pop\Db\Adapter\AbstractAdapter;
use Pop\Db\Sql\Seeder\AbstractSeeder;

class DatabaseSeeder extends AbstractSeeder
{
    
    public function run(AbstractAdapter $db): void
    {
        $sql = $db->createSql();
        
        $sql->insert('users')->values([
            'username' => 'testuser',
            'password' => '12test34',
            'email'    => 'test@test.com'
        ]);
        
        $db->query($sql);
    }
    
}
```

Then running the following command will execute any SQL in the seeder classes or any raw SQL in SQL files:

```bash
$ ./kettle db:seed
```

### Database Migrations

You can create the initial database migration that would modify your database schema as
your application grows by running the command:

```bash
$ ./kettle migrate:create <class> [<database>]
```

Where the `<class>` is the base class name of the migration class that will be created.
You will see your new migration class template in the `/database/migrations/<database>` folder:

```php
<?php

use Pop\Db\Sql\Migration\AbstractMigration;

class MyFirstMigration5dd822cdede29 extends AbstractMigration
{

    public function up(): void
    {

    }

    public function down(): void
    {

    }

} 
```

From there, you can populate the `up()` and `down()` with the schema to modify your database:

```php
<?php

use Pop\Db\Sql\Migration\AbstractMigration;

class MyFirstMigration5dd822cdede29 extends AbstractMigration
{

    public function up(): void
    {
        $schema = $this->db->createSchema();
        $schema->create('users')
            ->int('id', 16)->increment()
            ->varchar('username', 255)
            ->varchar('password', 255)
            ->varchar('email', 255)
            ->primary('id');
        
        $schema->execute();     
    }

    public function down(): void
    {
        $schema = $this->db->createSchema();
        $schema->drop('users');
        $schema->execute();
    }

}
```

You can run the migration and create the `users` table by running the command:

```bash
$ ./kettle migrate:run
```

And you can rollback the migration and drop the `users` table by running the command:

```bash
$ ./kettle migrate:rollback
```

### Migration State Storage

The migration state storage can be stored in one of two places. By default, it will store in a file called
`.current` in the database migration folder, for example:

```text
/database/migrations/default/.current
```

However, it can also be stored in the database itself in a separate migrations table. This requires a file
called `.table` to be placed in the database migration folder:

```text
/database/migrations/default/.table
```

The contents of the table will be the table class name for the migrations table in the database, for example:

```text
MyApp\Table\Migrations
```

[Top](#pop-kettle)

### Queue Commands

Kettle can configure and run a [pop-queue](https://github.com/popphp/pop-queue) worker against your
application's own command routes. Creating jobs and scheduled tasks is entirely your application's
responsibility (`$queue->addJob()` / `$queue->addTask()`, wherever that makes sense in your own code) —
these commands only configure the connection and run/administer what's already there.

- `queue:config [<queue>]` - configure a queue (File, Database, or Redis adapter). Defaults to `default`;
  pass any other name to configure an additional queue, the same way `db:config <database>` works.
- `queue:work [<queue>] [-o|--once] [-s|--sleep=]` - run the worker. Without `--once`, runs as a daemon
  until stopped (Ctrl+C). With `--once`, processes a single pass and exits - useful for a cron-driven
  setup instead of a supervised daemon. Pass `all` as `<queue>` to service every configured queue in one
  worker, weighted by each queue's configured weight.
- `queue:scheduler [<queue>] [-o|--once] [-s|--sleep=]` - same shape as `queue:work`, for scheduled tasks.
- `queue:clear [<queue>] [-f|--failed] [-t|--tasks]` - clear pending and in-flight (leased) jobs by
  default - **not** completed ones; `--failed` clears the dead-letter queue instead, `--tasks` clears
  scheduled tasks instead. Flags combine. Use with caution: run against a queue a worker is currently
  servicing, this drops undone work, including jobs that worker has already leased.
- `queue:jobs [<queue>]` - show pending and dead-letter job counts, and list dead-letter jobs with their
  failure reason.
- `queue:tasks [<queue>]` - list scheduled tasks with their cron expression and grace period.

[Top](#pop-kettle)

Creating Application Files
--------------------------

You can create skeleton application files with the `create` commands to assist you in wiring up various
MVC-based components, such as commands, controllers, model and views: 

```bash
./kettle create:command [-a|--app] <command>             Create a new CLI command, registered with Kettle
./kettle create:ctrl [--web] [--api] [--cli] <ctrl>      Create a new controller class
./kettle create:model [-d|--data] <model>                Create a new model class
./kettle create:view <view>                              Create a new view file
```

(See [Creating Custom Commands](#creating-custom-commands) below for more on `create:command`.)

For `create:ctrl`, `--web`, `--api`, and `--cli` may be combined to create more than one controller
class at once (one per flag), targeting whichever of those flavors were installed by `app:init`. If
none of the three flags are passed, a single generic HTTP controller is created under
`app/src/Http/Controller/` instead (regardless of whether the app was installed with `--web`, `--api`,
or both).

Once the respective class files or view scripts are created in the appropriate folders, you can then
open them up and begin writing your application code.

### Data Model

The `--data` option for the `create:model` command creates a model class that extends the
`Pop\Utils\AbstractDataModel` class, as well as a table class to interface with the corresponding
table in the database. For example, assuming the namespace of the application is `MyApp`, the command:

```bash
$ ./kettle create:model --data User
```

will create class files for `MyApp\Model\User` (extending the data model class `Pop\Db\Model\AbstractDataModel`)
and `MyApp\Table\Users`. From there, you can begin to store and retrieve data from the `users` table in the
database with very little additional coding.

[Top](#pop-kettle)

Creating Custom Commands
-------------------------

There are two ways to add custom, application-specific CLI commands to your project: registering
lightweight **Kettle Commands** directly with the `kettle` script itself, or building out a full,
separate **Application Console Script** of your own. Which one you reach for depends on the shape of
what you're building — and, as covered below, it's a decision you effectively make once, up front,
when you initialize the application.

### Kettle Commands

If your application only needs a handful of one-off CLI commands (e.g. "send a welcome email",
"deactivate a stale account"), you can register them directly with `kettle` without having to build
and wire up a separate console application. Each command is its own class with a single `handle()`
action — a 1:1 relationship between class and command.

```bash
$ ./kettle create:command [-a|--app] <command>
```

This requires that the application has already been initialized with the `--cli` flag (or a
combination that includes it, e.g. `--web --cli`), since the command class is scaffolded into
`app/src/Console/Command/`, which is only created for CLI-enabled installs.

The `<command>` value becomes both the CLI command signature and (in title case) the generated class
name — e.g. `./kettle create:command send-email` produces `app/src/Console/Command/Kettle/SendEmail.php`,
namespaced `MyApp\Console\Command\Kettle`:

```php
<?php

namespace MyApp\Console\Command\Kettle;

class SendEmail extends \Pop\Console\Command\AbstractCommand
{

    public ?string $name = 'send-email';
    public ?string $params = null;
    public ?string $help = 'This is the send-email command';

    public function handle()
    {
        /** Add command code here. */
    }

}
```

You can namespace the command signature itself by including a colon, e.g.
`./kettle create:command email:send`, which still produces a class named `Send` (based on the part
after the last `:`), but registers the full `email:send` string as the command. If the command takes
CLI arguments or options, add them to the `$params` property using the standard `Pop\Router` CLI
syntax (`<required>`, `[<optional>]`, `[--flag]`, `[-s|--long=]`), for example:

```php
public ?string $params = '<to> [--cc=]';
```

By default (without `-a`/`--app`), commands are scaffolded into the `Kettle` subfolder shown above.
Every class found there is automatically discovered and merged into `kettle`'s own route table on
every run — there's nothing further to wire up, and the command shows up in `./kettle help` alongside
Kettle's own built-in commands. Once created, run it like any other `kettle` command:

```bash
$ ./kettle send-email
$ ./kettle email:send test@test.com --cc=someone@test.com
```

Even though it's invoked through `kettle`, the command doesn't actually run *as* Kettle. Once `kettle`
matches the route to a non-native controller, it hands execution off to your own application's
`Application` class (the one at `app/src/Application.php`) and runs the command through that instead —
so inside `handle()`, `$this->application` is your app, with access to whatever services, config and
database connection your app's own `Application::load()` sets up, not Kettle's internal ones. This
"boot through Kettle, then switch to your app" behavior is what makes it possible to register commands
against your own application's namespace without building and maintaining a second, separate console
script.

Pass `-a`/`--app` to scaffold the command into `app/src/Console/Command/` directly instead (no `Kettle`
subfolder, and no `Kettle` namespace segment). Commands created this way are **not** merged into
`kettle`'s route table and can't be run as `./kettle <command>` — they only exist for the stand-alone
[Application Console Script](#application-console-scripts) described below, which auto-discovers them
the same way `kettle` auto-discovers its own `Kettle`-subfolder commands. That means a stand-alone
script must already exist for an `--app` command to be reachable at all — see the note about that in
the next section before using this flag.

### Application Console Scripts

For a CLI application with a larger number of related commands, it's often cleaner to build a fully
separate, self-contained console application than to keep piggybacking Kettle Commands onto `kettle`
itself. Whether that stand-alone script exists at all is a one-time decision made during
`app:init --cli` (or a flag combination including `--cli`): you're prompted "Initialize a stand-alone
CLI application?", and if you accept, its main script is scaffolded at `script/<namespace>` (see
[Initializing an Application](#initializing-an-application) above for the exact naming rule). This
choice isn't easily reversible after the fact — decide up front whether you want a second, independent
console application, or to keep everything running through `kettle` as Kettle Commands.

If you accepted the prompt, the stand-alone script's route table is built from three sources: its own
baseline `help`/error handling (routed to a `Console\Controller\ConsoleController` class), any
additional controllers you add with `./kettle create:ctrl --cli <ctrl>` (grouping related commands
together under a class — e.g. an `EmailController` housing several email-related actions, instead of a
separate `SendEmail`/`QueueEmail`/`RetryEmail` command class for each one), and any `-a`/`--app`
commands created as described above. See [Accessing the Application](#accessing-the-application)
below for how to run the script.

If you declined the prompt, none of that scaffolding exists — `app/src/Console/Controller` and
`script/` are never created — and `./kettle create:ctrl --cli <ctrl>` will refuse with an explicit
error rather than silently failing:

```text
Error: This application was not initialized with a stand-alone console application.
```

In short: reach for a **Kettle Command** for a small number of standalone commands you want available
immediately with no extra wiring, running through your app's own `Application` class via `kettle`;
reach for an **Application Console Script** when you want a larger, fully independent CLI application
with its own namespaced groups of commands, decided once up front at `app:init` time.

[Top](#pop-kettle)

Running the Web Server
----------------------

`pop-kettle` also provides a simple way to run PHP's built-in web-server, by running the command:

```bash
$ ./kettle serve [--host=] [--port=] [--folder=]
```

If omitted, `--host` defaults to `localhost`, `--port` defaults to `8000`, and `--folder` defaults
to `public`.

This is for development environments only and it is strongly advised against using the built-in
web server in a production environment in any way.

[Top](#pop-kettle)

Getting Help and Version
-----------------------

To see the full list of available commands with their descriptions:

```bash
$ ./kettle help
```

Pass `--raw` (or `-r`) to print the help screen without ANSI color codes, useful when piping the
output somewhere that doesn't render them:

```bash
$ ./kettle help --raw
```

To see the currently installed version of `pop-kettle`:

```bash
$ ./kettle version
```

[Top](#pop-kettle)

Accessing the Application
-------------------------

If you have wired up the beginnings of an application, you can then access the default routes
in the following ways. Assuming you've started the web server as described above using
`./kettle serve`, you can access the web application by going to the address `http://localhost:8000/`
in any web browser and seeing the default index HTML page.

If you create both a web and API application, the HTML application will be accessible at `http://localhost:8000/`.
If you want to access the API application, the default route for that is `http://localhost:8000/api`
and you can access it like this to see the default JSON response:

```bash
$ curl -i -X GET http://localhost:8000/api
```

And, if you `cd script`, you'll see the default CLI application that was created. The default
route available to the CLI application is the `help` route:

```bash
$ ./app help
```
[Top](#pop-kettle)

Shell Completion
----------------

Shell completion for both `bash` and `zsh` shells is available. Simply copy the correct shell completion
file to your user home directory and add them via the `source` command to your shell's read command file.

### BASH

```bash
cp .kettle.bash ~/
```

Edit the `~/.bashrc` file and add this:

```bash
source ~/.kettle.bash
```

### ZSH

```bash
cp .kettle.zsh ~/
```

Edit the `~/.zshrc` file and add this:

```bash
source ~/.kettle.zsh
```

Once you've set up your preferred shell, close all terminal windows and re-open a new one. Change directory to
any project that has the `kettle` script in it and the auto-completion should now be available.

[Top](#pop-kettle)

Using on Windows
----------------

Most UNIX-based environments should recognize the main `kettle` application script as a PHP
script and run it accordingly, without having to explicitly call the `php` command and pass
the script and its parameters into it. However, if you're on an environment like Windows,
depending on your exact environment set up, you will most likely have to prepend all of the
command calls with the `php` command, for example:

```bash
C:\popphp\pop-kettle>php kettle help
``` 
