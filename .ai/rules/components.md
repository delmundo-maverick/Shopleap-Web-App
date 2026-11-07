---
paths:
  - 'resources/views/components/nav*/**'
---

# Components

## Use the shared navigation icon registry
Repeated sidebar concepts use App\Support\NavigationIcons; x-nav.workspace resolves paths by named route and x-nav.icon reuses them in dashboard actions. Keep inline SVG at 20px, stroke 1.7, round caps/joins and currentColor. Preserve labels, links and active-state selection when replacing icon paths.
