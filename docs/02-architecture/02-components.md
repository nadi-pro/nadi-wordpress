# Components

Core classes and their responsibilities in the Nadi WordPress plugin.

## Entry Point

### nadi.php

The plugin bootstrap file:

- Defines constants: `NADI_VERSION`, `NADI_DIR`, `NADI_START`
- Validates PHP version and Composer dependencies
- Registers activation/deactivation hooks
- Sets global exception handler
- Initializes the `Nadi` orchestrator

## Core Classes

### Nadi (`src/Nadi.php`)

Main orchestrator that:

- Handles plugin lifecycle (activate/deactivate)
- Processes form submissions from admin settings
- Coordinates config updates

### Config (`src/Config.php`)

Configuration manager that:

- Manages YAML config files for both transporters
- Stores/retrieves WordPress options (`nadi_*`)
- Handles sampling configuration

### Loader (`src/Loader.php`)

WordPress integration that:

- Registers admin menu and settings page
- Handles WordPress hooks (`init`, `admin_init`, `admin_menu`)
- Renders settings form

### Shipper (`src/Shipper.php`)

Shipper binary wrapper that:

- Downloads binary from GitHub releases
- Detects platform (OS/architecture)
- Executes binary to send log files
- Manages installation/uninstallation

## Exception Handling

### HandleExceptionEvent (`src/Handler/HandleExceptionEvent.php`)

Global exception handler that:

- Captures exceptions via `::make()` static factory
- Creates `WordPressException` with context
- Collects metrics and sends via transporter

### Base (`src/Handler/Base.php`)

Base handler that:

- Initializes transporter (Log or HTTP)
- Configures sampling strategy
- Provides `store()` and `send()` methods

### WordPressException (`src/Exceptions/WordPressException.php`)

Exception wrapper that:

- Holds exception context (trace, file, line)
- Stores WordPress-specific error data

## Metrics

Metrics classes in `src/Metric/`:

| Class       | Purpose                                    |
|-------------|--------------------------------------------|
| Http        | Request method, duration, status, headers  |
| Framework   | WordPress version                          |
| Application | Environment (production, staging, etc.)    |
| Network     | Protocol, host, port                       |

## Concerns (Traits)

Traits in `src/Concerns/`:

| Trait                      | Purpose                        |
|----------------------------|--------------------------------|
| InteractsWithEnvironment   | Environment detection          |
| InteractsWithSettings      | WordPress options access       |
| InteractsWithUser          | Current user information       |
| InteractsWithMetric        | Metric collection utilities    |

## Data Classes

Data classes in `src/Data/`:

| Class          | Purpose                        |
|----------------|--------------------------------|
| Entry          | Base entry structure           |
| ExceptionEntry | Exception-specific entry data  |

## Next Steps

- [Architecture Overview](01-overview.md)
- [Transporters](../03-configuration/01-transporters.md)
