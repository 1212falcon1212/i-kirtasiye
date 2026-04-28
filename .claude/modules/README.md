# UCES Extension Modules

This directory is for custom extension modules.

## Creating a Module

1. Create a new directory: `modules/my-module/`
2. Add a `MODULE.md` file with the required frontmatter
3. Restart Claude Code

## Module Structure

```
modules/
└── my-module/
    ├── MODULE.md      # Module definition (required)
    ├── templates/     # Optional templates
    └── examples/      # Optional examples
```

## MODULE.md Format

```yaml
---
module: my-module
title: "My Custom Module"
triggers: ["keyword1", "keyword2"]
version: 1.0.0
tools:
  - Read
  - Edit
  - Write
---

# Module Title

Module content and instructions...
```
