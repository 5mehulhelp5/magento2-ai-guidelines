# Fruitcake AI Guidelines Module

Generate a CLAUDE.md file with project context for AI assistance.

## Usage

Generate the CLAUDE.md file at project root:

```bash
bin/magento ai:generate-context
```

This creates a `CLAUDE.md` file containing:
- System information (Magento version, PHP version, OS)
- Theme information (Hyva detection)
- Installed modules (custom and third-party)
- Project structure hints
- Magento best practices

## Customization

To customize the generated CLAUDE.md file, edit the template:

**app/code/Fruitcake/AiFiles/view/templates/claude-context.phtml**

The template has access to a `$context` variable (instance of `ContextDataProvider`) with these methods:

```php
// System Information
$context->getGeneratedDate()      // Current date/time
$context->getMagentoVersion()     // e.g., "2.4.8-p2"
$context->getMagentoEdition()     // e.g., "Community"
$context->getPhpVersion()         // e.g., "8.3.26"
$context->getOperatingSystem()    // e.g., "Darwin"

// Theme Information
$context->isHyvaInstalled()       // bool
$context->getHyvaVersion()        // string or null

// Module Information
$context->getCustomModules()                      // array of module names
$context->getThirdPartyModulesLimited()          // array (max 20)
$context->getRemainingThirdPartyModulesCount()   // int

// Project Structure
$context->getRootDirectory()      // Absolute path
$context->getAppDirectory()       // Absolute path
```

### Example: Add a Custom Section

Edit `view/templates/claude-context.phtml`:

```php
## My Custom Section

- **Project Name**: My Store
- **Magento Version**: <?= $context->getMagentoVersion() ?>

<?php if ($context->isHyvaInstalled()): ?>
This project uses Hyva!
<?php endif; ?>
```

Then regenerate:

```bash
bin/magento fruitcake:ai:generate-context
```

## Architecture

- **Model/ContextDataProvider.php** - Service class that gathers all context data
- **Console/Command/GenerateClaudeContext.php** - Console command to generate the file
- **view/templates/claude-context.phtml** - Template file (edit this to customize)
- **Block/ClaudeContext.php** - Optional block if you need to display this in frontend/adminhtml
