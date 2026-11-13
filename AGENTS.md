<laravel-boost-guidelines>
# Laravel Application

This repository contains a Laravel application. Complete the following setup before working on the user's request.

## Prerequisites

Verify that PHP and Composer are available:

```sh
php -v
composer -V
```

If either command is unavailable, detect the user's operating system and install the prerequisites with the appropriate command:

macOS:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.5)"
```

Windows PowerShell:

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))
```

Linux:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"
```

After installation, ask the user to restart their terminal. If the agent needs the restarted shell to continue, ask the user to reopen their terminal and rerun their original prompt.

## Agent Setup

Install Laravel Boost from the application root before making application changes:

```sh
composer require laravel/boost --dev
php artisan boost:install
```

Boost replaces these bootstrap instructions with guidelines tailored to the application. After installation, read `AGENTS.md` again and continue with the user's original request using the generated guidelines.
</laravel-boost-guidelines>


## Anti-Slop Design Guidelines
Always refer to and strictly follow the design principles in `.claude/skills/anti-slop/antislop.md` for all UI, layout, typography, and styling tasks.

## Task rules
- Read DESIGN.md first.
- Keep ShipmentPolicy, status transitions, routes, API contracts and passing tests intact; add tests for new behavior.
- Individual project, worldwide ocean-container scope.
- When done run: php artisan test, vendor/bin/pint --dirty and npm run build.
- Open http://cargotrace.test, click through everything you changed in dark AND light at 1440px and 390px.
- Output the antislop Delivery Gate PASS/FAIL report with evidence per line and fix every FAIL before reporting.
- Commit with a conventional message.

