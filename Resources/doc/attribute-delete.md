# Delete an attribute

### Notes
You can't delete an identifier attribute.

Then, the following steps are done:
- Remove attribute axis from the variant groups. If you remove the only attribute of the variant group, the group will be removed.

- Delete published product values linked to this attribute

- Reschedule published completeness for affected families

- Call the PIM attribute remover to properly remove the attribute. This will reschedule completeness for simple products.


### Instructions

Launch the command `pim:dev-toolbox:attribute:delete` with your attribute code in `--attribute` option.

Then the completeness of impacted products and published products will need to be recalculate.
A command has been added in this bundle to allow to calculate completeness on published products.
To relaunch the completeness on products and published products, you can respectively launch the following commands:
- `php app/console pim:completeness:calculate`
- `php app/console pim:dev-toolbox:published_completeness:calculate`


### Impacts

- Removing the only attribute of a variant group will remove the variant group.
- Revert will fail cause the versioning does not make sense now.
- Drafts display diff on removed attribute but they are not taken in account.


### TODO
Make it work on CE and with MongoDB
