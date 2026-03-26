# Symfony Profiler MCP

Symfony bundle that exposes profiler data to AI-powered IDEs via the [Model Context Protocol](https://modelcontextprotocol.io/). Built on [symfony/mcp-bundle](https://symfony.com/doc/current/ai/bundles/mcp-bundle.html).

**By [ProductOwner.ro](https://productowner.ro) in collaboration with Claude.**

> Inspired by [killerwolf/mcp-profiler-bundle](https://github.com/killerwolf/mcp-profiler-bundle). Rewritten from scratch to fix context bloat, broken tool names, and missing data redaction.

## Requirements

- PHP >= 8.2
- Symfony >= 7.3
- [symfony/mcp-bundle](https://github.com/symfony/mcp-bundle) >= 0.6

## Installation

```bash
composer require --dev productowner-ro/symfony-profiler-mcp
```

The Symfony Flex recipe configures `symfony/mcp-bundle` transports and routes automatically. If your project doesn't use Flex, create the following files manually:

<details>
<summary>Manual configuration (without Flex)</summary>

**`config/packages/mcp.yaml`**

```yaml
when@dev:
    mcp:
        client_transports:
            stdio: true   # For Claude Code, JetBrains, etc.
            http: true    # For /_mcp HTTP endpoint
```

**`config/routes/mcp.yaml`** (only needed if using HTTP transport)

```yaml
when@dev:
    mcp:
        resource: .
        type: mcp
```

Then clear the cache and verify:

```bash
php bin/console cache:clear
php bin/console mcp:server   # Should start without errors (Ctrl+C to stop)
```

</details>

## How It Works

This bundle uses a **two-tier data model** to keep context size minimal:

1. **Tools** return compact summaries with `resource_uri` pointers
2. **Resources** provide full detail only when explicitly requested

```
AI Agent calls tool              AI Agent reads resource (only if needed)
        |                                      |
  "list profiles"                   "give me the request
   "get profile X"                   collector for token X"
        |                                      |
        v                                      v
  Compact summary             symfony-profiler://profile/{token}/{collector}
  + resource_uri                               |
                                               v
                                 Formatted, redacted collector data
```

### MCP Tools

| Tool | Description |
|------|-------------|
| `symfony-profiler-list` | List recent profiles with filters (method, status, url, ip, limit) |
| `symfony-profiler-get` | Get profile summary with available collector URIs |

### MCP Resource Templates

| URI Template | Description |
|--------------|-------------|
| `symfony-profiler://profile/{token}` | Profile overview with list of collectors |
| `symfony-profiler://profile/{token}/{collector}` | Formatted collector data |

### Key Differences from killerwolf/mcp-profiler-bundle

| Issue | Original | This Bundle |
|-------|----------|-------------|
| Tool names | `profiler:list` (breaks Claude Code) | `symfony-profiler-list` (API-compliant) |
| Context size | Dumps ALL collectors at once (megabytes) | Two-tier: summaries first, detail on demand |
| Response format | Mixed JSON + VarDumper text | Always clean JSON |
| Security | Exposes cookies, auth headers, server vars | Automatic redaction of sensitive data |
| Code duplication | Path logic copy-pasted 4x | Single `ProfilerStorageResolver` |
| MCP protocol | 220 lines hand-rolled JSON-RPC | Uses `symfony/mcp-bundle` (zero protocol code) |
| PHP 8.4 | Deprecation warnings | Clean support |

## Multi-App Support

The bundle auto-discovers profiler directories in multi-app Symfony setups (e.g., `var/cache/app1_hash/dev/profiler`). No configuration needed.

## Custom Collector Formatters

Implement `CollectorFormatterInterface` and tag your service:

```php
use ProductOwner\SymfonyProfilerMcp\Profiler\Formatter\CollectorFormatterInterface;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;

class MyCollectorFormatter implements CollectorFormatterInterface
{
    public function getName(): string
    {
        return 'my_collector';
    }

    public function format(DataCollectorInterface $collector): array
    {
        return ['key' => 'formatted data'];
    }

    public function getSummary(DataCollectorInterface $collector): array
    {
        return ['key' => 'compact summary'];
    }
}
```

The `CollectorFormatterInterface` is auto-tagged. Just register your class as a service.

## IDE Configuration

### Claude Code

```json
{
  "mcpServers": {
    "symfony-profiler": {
      "command": "php",
      "args": ["bin/console", "mcp:server"],
      "cwd": "/path/to/your/symfony/project"
    }
  }
}
```

### Cursor / Windsurf

Add to your MCP configuration following the same pattern.

## License

MIT - See [LICENSE](LICENSE).
