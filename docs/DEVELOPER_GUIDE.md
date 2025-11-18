# Developer Guide - B2BRouter PHP SDK

Quick guide for setting up and working with the B2BRouter PHP SDK.

## Table of Contents

- [Quick Setup](#quick-setup)
- [IDE Setup](#ide-setup)
  - [Neovim](#neovim-php-setup)
  - [VS Code](#vs-code-php-setup)
  - [PHPStorm](#phpstorm-php-setup)
- [Running Tests](#running-tests)
- [Working with Examples](#working-with-examples)
- [Best Practices](#best-practices)

---

## Quick Setup

### 1. Install PHP and Dependencies

```bash
# Install PHP and required extensions (Debian/Ubuntu)
sudo apt update
sudo apt install -y \
    php-cli \
    php-curl \
    php-mbstring \
    php-xml \
    composer

# Verify installation
php --version
composer --version
```

### 2. Clone and Setup

```bash
# Clone the repository
git clone https://github.com/yourusername/b2b-php.git
cd b2b-php

# Install dependencies
composer install

# Run tests to verify setup
vendor/bin/phpunit
```

### 3. Configure Environment

Create a `.env` file for your API credentials:

```bash
# .env (never commit this file!)
B2B_API_KEY=your_api_key_here
B2B_ACCOUNT_ID=your_account_id
B2B_API_BASE=https://api-staging.b2brouter.net
```

---

## IDE Setup

### Neovim PHP Setup

This section assumes you already have Neovim installed and configured with a plugin manager (lazy.nvim, packer, etc.).

### Install PHP Language Server

#### Option 1: Install with Mason (Recommended)

If you have Mason installed:

```vim
:Mason
```

Search for and install:
- `phpactor` (PHP language server)

#### Option 2: Manual Installation

Install Phpactor manually if you don't use Mason:

```bash
# Install via composer globally
composer global require phpactor/phpactor

# Make sure composer global bin is in your PATH
export PATH="$HOME/.composer/vendor/bin:$PATH"

# Or download the standalone PHAR
curl -Lo phpactor https://github.com/phpactor/phpactor/releases/latest/download/phpactor.phar
chmod +x phpactor
sudo mv phpactor /usr/local/bin/phpactor
```

### LSP Configuration

Add to your Neovim LSP config. If you have a list of servers in the style of Kickstarter
neovim configuration, just add phpactor:

```lua
```lua
local servers = {
  -- clangd = {},
  -- rust_analyzer = {},
  -- tsserver = {},

  lua_ls = {
    settings = {
      Lua = {
        workspace = { checkThirdParty = false },
        telemetry = { enable = false },
        diagnostics = {globals = { 'vim' }},
      },
    },
  },
  pyright = {},
  ruby_lsp = {},
  gopls = {},
  phpactor = {},
}
```

### PHP-Specific Keybindings (Optional)

Add PHP-specific shortcuts:

```lua
-- Add to your config
vim.api.nvim_create_autocmd("FileType", {
  pattern = "php",
  callback = function()
    local opts = { buffer = true, noremap = true, silent = true }

    -- Run tests
    vim.keymap.set("n", "<leader>t", ":!vendor/bin/phpunit<CR>", opts)
    vim.keymap.set("n", "<leader>tf", ":!vendor/bin/phpunit %<CR>", opts)

    -- Syntax check
    vim.keymap.set("n", "<leader>ps", ":!php -l %<CR>", opts)

    -- Composer
    vim.keymap.set("n", "<leader>ci", ":!composer install<CR>", opts)
  end,
})
```

---

### VS Code PHP Setup

#### Option 1: Phpactor

Install the Phpactor extension:

```bash
# Via command line
code --install-extension phpactor.vscode-phpactor

# Or via VS Code: Ctrl+P (Cmd+P on Mac), then:
ext install phpactor.vscode-phpactor
```

**Install phpactor binary:**

```bash
# Via composer (recommended)
composer global require phpactor/phpactor

# Ensure composer global bin is in PATH
export PATH="$HOME/.composer/vendor/bin:$PATH"

# Or download PHAR
curl -Lo phpactor https://github.com/phpactor/phpactor/releases/latest/download/phpactor.phar
chmod +x phpactor
sudo mv phpactor /usr/local/bin/phpactor
```

Add to `.vscode/settings.json`:

```json
{
  "phpactor.enable": true,
  "php.validate.executablePath": "/usr/bin/php",
  "editor.formatOnSave": false
}
```

#### Option 2: PHP Intelephense

Alternative language server:

```bash
code --install-extension bmewburn.vscode-intelephense-client
```

Add to `.vscode/settings.json`:

```json
{
  "intelephense.files.maxSize": 5000000,
  "intelephense.environment.phpVersion": "7.4.0",
  "php.validate.executablePath": "/usr/bin/php",
  "editor.formatOnSave": false
}
```

#### Optional: PHP Debug

For debugging support with Xdebug:

```bash
code --install-extension xdebug.php-debug
```

#### Run Tests in VS Code

Add to `.vscode/tasks.json`:

```json
{
  "version": "2.0.0",
  "tasks": [
    {
      "label": "Run PHPUnit Tests",
      "type": "shell",
      "command": "vendor/bin/phpunit",
      "group": {
        "kind": "test",
        "isDefault": true
      },
      "presentation": {
        "reveal": "always",
        "panel": "new"
      }
    }
  ]
}
```

Run tests with: `Ctrl+Shift+B` (Cmd+Shift+B on Mac) or Terminal > Run Task > Run PHPUnit Tests

---

### PHPStorm PHP Setup

PHPStorm has built-in PHP support. Just configure the PHP interpreter and Composer.

#### Configure PHP Interpreter

1. Go to **File > Settings > PHP** (or **PHPStorm > Preferences > PHP** on Mac)
2. Click the **...** button next to CLI Interpreter
3. Click **+** and select **Other Local...**
4. Set PHP executable path (e.g., `/usr/bin/php`)
5. Click **OK**

#### Configure Composer

1. Go to **File > Settings > PHP > Composer**
2. Set path to composer.json: `<project_root>/composer.json`
3. Set Composer executable path (e.g., `/usr/bin/composer`)
4. Click **OK**

#### Run Tests in PHPStorm

1. Right-click on `tests/` folder or any test file
2. Select **Run 'tests'** or **Run '<TestFileName>'**
3. Or use keyboard shortcut: `Ctrl+Shift+F10` (Cmd+Shift+R on Mac)

#### Configure PHPUnit

If tests don't run automatically:

1. Go to **File > Settings > PHP > Test Frameworks**
2. Click **+** > **PHPUnit Local**
3. Set PHPUnit library path: `<project_root>/vendor/autoload.php`
4. Use Composer autoloader: Enabled
5. Click **OK**

---

## Running Tests

### Basic Usage

```bash
# Run all tests
vendor/bin/phpunit

# Run with detailed output
vendor/bin/phpunit --testdox

# Run specific test file
vendor/bin/phpunit tests/Unit/InvoiceServiceTest.php

# Run specific test method
vendor/bin/phpunit --filter testCreateInvoice

# Generate coverage report (requires xdebug)
vendor/bin/phpunit --coverage-html coverage
```

### Test Structure

```
tests/
├── Unit/              # Unit tests
│   ├── InvoiceServiceTest.php
│   ├── CollectionTest.php
│   └── ExceptionTest.php
├── Mock/              # Mock objects
│   └── MockHttpClient.php
└── TestCase.php       # Base test class
```

---

## Working with Examples

All examples are in the `examples/` directory and use environment variables for configuration.

### Setup Environment

1. **Copy the environment template:**
   ```bash
   cp .env.example .env
   ```

2. **Edit `.env` and add your credentials:**
   ```env
   B2B_API_KEY=your-api-key-here
   B2B_ACCOUNT_ID=your-account-id
   # B2B_API_BASE=https://api.b2brouter.net  # Uncomment for production (defaults to staging)
   ```

   Get your API key from: https://app.b2brouter.net

   **Note:** The SDK defaults to the staging environment (`https://api-staging.b2brouter.net`) for safe testing.

3. **Install dependencies:**
   ```bash
   composer install
   ```

### Run Examples

```bash
# Invoice examples
php examples/invoices.php
php examples/create_simple_invoice.php
php examples/list_invoices.php

# Tax report examples
php examples/tax_reports.php
php examples/verifactu_tax_report.php
php examples/ticketbai_tax_report.php
```

All examples automatically load credentials from `.env` via the `examples/bootstrap.php` helper.

### Available Examples

**Invoice Examples:**
- `invoices.php` - Complete CRUD operations demo
- `create_simple_invoice.php` - Simple invoice creation
- `create_detailed_invoice.php` - Multi-line invoice
- `list_invoices.php` - List and filter invoices
- `paginate_all_invoices.php` - Pagination example
- `update_invoice.php` - Update existing invoice
- `invoice_workflow.php` - Complete workflow from creation to sending
- `invoicing_in_spain_with_verifactu.php` - Spanish Verifactu compliance

**Tax Report Examples:**
- `tax_reports.php` - Complete CRUD operations for VeriFactu
- `verifactu_tax_report.php` - VeriFactu-specific workflow
- `ticketbai_tax_report.php` - TicketBAI-specific workflow
- `list_tax_reports.php` - Listing and filtering

---

## Best Practices

### 1. Error Handling

Always catch specific exceptions:

```php
use B2BRouter\Exception\{
    AuthenticationException,
    InvalidRequestException,
    ResourceNotFoundException,
    ApiErrorException
};

try {
    $invoice = $client->invoices->create($accountId, $params);
} catch (InvalidRequestException $e) {
    // Handle validation errors
    $errors = $e->getJsonBody()['errors'] ?? [];
    // Log or display errors
} catch (AuthenticationException $e) {
    // Handle auth errors
} catch (ResourceNotFoundException $e) {
    // Handle 404 errors
} catch (ApiErrorException $e) {
    // Handle other API errors
    error_log("API Error: {$e->getMessage()}, Status: {$e->getHttpStatus()}");
}
```

### 2. Pagination

Handle large datasets properly:

```php
$offset = 0;
$limit = 100;

do {
    $page = $client->invoices->all($accountId, [
        'limit' => $limit,
        'offset' => $offset
    ]);

    foreach ($page as $invoice) {
        processInvoice($invoice);
    }

    $offset += $limit;
} while ($page->hasMore());
```

### 3. Environment-Specific Config

```php
$config = [
    'development' => [
        'api_base' => 'https://api-staging.b2brouter.net',
        'api_key' => getenv('B2B_DEV_API_KEY'),
    ],
    'production' => [
        'api_base' => 'https://api.b2brouter.net',
        'api_key' => getenv('B2B_PROD_API_KEY'),
    ],
];

$env = getenv('APP_ENV') ?: 'development';
$client = new B2BRouterClient($config[$env]['api_key'], $config[$env]);
```

### 4. Logging Requests (Debug)

Wrap HTTP client for debugging:

```php
class LoggingHttpClient implements ClientInterface
{
    private $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    public function request($method, $url, $headers, $body, $timeout)
    {
        error_log("API Request: {$method} {$url}");
        $response = $this->client->request($method, $url, $headers, $body, $timeout);
        error_log("API Response: {$response['status']}");
        return $response;
    }
}

// Use it
$httpClient = new LoggingHttpClient(new CurlClient());
$client = new B2BRouterClient('api-key', ['http_client' => $httpClient]);
```

---

## Troubleshooting

### Common Issues

**PHPUnit not found**
```bash
# Make sure dependencies are installed
composer install

# Or use full path
./vendor/bin/phpunit
```

**SSL Certificate errors**
```bash
# Install ca-certificates
sudo apt install ca-certificates

# Or disable SSL verification (not recommended for production)
# Add to php.ini: curl.cainfo=/etc/ssl/certs/ca-certificates.crt
```

**Composer out of memory**
```bash
php -d memory_limit=-1 $(which composer) install
```

---

## Quick Command Reference

```bash
# PHP
php --version                      # Check PHP version
php -m                             # List extensions
php -l file.php                    # Check syntax

# Composer
composer install                   # Install dependencies
composer update                    # Update dependencies
composer dump-autoload             # Regenerate autoloader

# PHPUnit
vendor/bin/phpunit                 # Run all tests
vendor/bin/phpunit --testdox       # Detailed output
vendor/bin/phpunit --filter name   # Run specific test

# Git
git status                         # Check status
git add .                          # Stage changes
git commit -m "message"            # Commit
git push                           # Push to remote
```

---

## Additional Resources

- [B2BRouter API Documentation](https://developer.b2brouter.net)
- [PHP Documentation](https://www.php.net/docs.php)
- [Composer Documentation](https://getcomposer.org/doc/)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)

---

For issues or questions, contact: servicedelivery@b2brouter.net
