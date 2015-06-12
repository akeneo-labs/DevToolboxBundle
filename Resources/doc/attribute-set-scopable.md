# Define an attribute as scopable

### Notes
You can't set an identifier as scopable

Then, the following steps are done:
- Remove attribute axis from the variant groups. A scopable attribute can't be defined as axis.

- Move current product values and published product values to the defined scope.

- Reschedule completeness for products and published products

- Define the attribute as scopable and save it.


### Instructions

Launch the `pim:dev-toolbox:attribute:set-scopable` with your attribute code in `--attribute` option and your channel code in `--scope` option

Then the completeness of impacted products and published products will need to be recalculate.
A command has been added in this bundle to allow to calculate completeness on published products.
To relaunch the completeness on products and published products, you can respectively launch the following commands:
- `php app/console pim:completeness:calculate`
- `php app/console pim:dev-toolbox:published_completeness:calculate`


### Impacts

- Set scopable a variant group axis will remove it from the variant group
- Revert will fail cause the versioning does not make sense now.
- Drafts display diff on removed attribute but they are not taken in account.


### TODO
Make it work on CE and with MongoDB
