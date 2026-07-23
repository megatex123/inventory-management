---
tags: [module, hr, ops]
---

# HR / Operations

Basic back-office CRUD modules with **no relationship to the customer/order flow** in [[Workflow]] — no foreign keys into `order`, `customers`, or any of the tier systems. This note exists mainly so their absence from [[Workflow]] doesn't read as an oversight.

## Models

- **`Employees`** — staff records: `name`, `email`, `phone`, `address`, `sallery` (base salary rate — column name is misspelled and that's the real column, not a typo to fix casually since it's referenced elsewhere), `photo`, `nid`, `join_date`. Standard `apiResource` CRUD at `/employee`.
- **`Salaries`** — one row per (employee, month, year) payout, `emp_id` → `Employees`. **Not** a standard `apiResource` — no `index`/`show`/`edit` in `SalariesController` are actually implemented (`index` is an empty stub; `edit`/`update`/`destroy` are unfilled Laravel scaffold boilerplate with a lowercase `salaries $salaries` type-hint that was never wired to a route). The real behavior lives in three custom methods instead:
  - `POST /salary/paid/{id}` — `SalariesController::paid()`: marks a given employee (`{id}` = `emp_id`) paid for `salary_month` (request param) + the current year. Checks for an existing paid row first (same `emp_id`/`salary_month`/`salary_year`) and returns `"Salary Alrady Paid"` (sic — real string) if found, otherwise creates one from `request->sallery`. Note this reads `salary_year` as `date('Y')` (server's current year) unconditionally — there's no way to pay a salary for a past year through this endpoint.
  - `GET /salary` — `SalariesController::salary()`: returns the distinct list of `salary_month` values that have at least one paid row (drives a month-picker UI, not a list of salaries itself).
  - `GET /salaryview/{id}` — `SalariesController::salaryview()`: **`{id}` here is a `salary_month` string, not a numeric ID** despite the route param name — joins `employees` and returns all salary rows for that month.
- **`Expenses`** — general expense logging (`details`, `amount`), not tied to employees or orders. Standard `apiResource` CRUD at `/expens` (route prefix is the truncated/misspelled form, not `/expenses`).

## Related
- [[Workflow]]
- [[Domain-Models]]
- [[API-Routes]] — standard `apiResource` CRUD (`employee`, `expens`) plus the custom `Salaries` routes above
