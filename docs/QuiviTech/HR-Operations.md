---
tags: [module, hr, ops]
---

# HR / Operations

Basic back-office CRUD modules with **no relationship to the customer/order flow** in [[Workflow]] — no foreign keys into `order`, `customers`, or any of the tier systems. This note exists mainly so their absence from [[Workflow]] doesn't read as an oversight.

## Models

- **`Employees`** — staff records.
- **`Salaries`** — payroll entries, `emp_id` → `Employees`.
- **`Expenses`** — general expense logging, not tied to employees or orders.

## Related
- [[Workflow]]
- [[Domain-Models]]
- [[API-Routes]] — standard `apiResource` CRUD
