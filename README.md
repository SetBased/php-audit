# PhpAudit

<table>
<thead>
<tr>
<th>Social</th>
<th>Legal</th>
<th>Release</th>
<th>Tests</th>
<th>Code</th>
</tr>
</thead>
<tbody>
<tr>
<td>
<a href="https://gitter.im/SetBased/php-audit?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge"><img src="https://badges.gitter.im/SetBased/php-audit.svg" alt="Gitter"/></a>
</td>
<td>
<a href="https://packagist.org/packages/setbased/php-audit"><img src="https://poser.pugx.org/setbased/php-audit/license" alt="License"/></a>
</td>
<td>
<a href="https://packagist.org/packages/setbased/php-audit"><img src="https://poser.pugx.org/setbased/php-audit/v/stable" alt="Latest Stable Version"/></a><br/>
</td>
<td>
<a href="https://travis-ci.org/SetBased/php-audit"><img src="https://travis-ci.org/SetBased/php-audit.svg?branch=master" alt="Build Status"/></a><br/>
<a href="https://scrutinizer-ci.com/g/SetBased/php-audit/?branch=master"><img src="https://scrutinizer-ci.com/g/SetBased/php-audit/badges/coverage.png?b=master" alt="Code Coverage"/></a>
</td>
<td>
<a href="https://scrutinizer-ci.com/g/SetBased/php-audit/?branch=master"><img src="https://scrutinizer-ci.com/g/SetBased/php-audit/badges/quality-score.png?b=master" alt="Scrutinizer Code Quality"/></a><br/>
</td>
</tr>
</tbody>
</table>

PhpAudit is a tool for creating and maintaining audit tables and triggers for creating audit trails of data changes in MySQL databases.


## Features

PhpAudit has the following features:
* Creates audit tables for tables in your database for which auditing is required.
* Creates triggers on tables for recording inserts, updates, and deletes of rows.
* Helps you to maintain audit tables and triggers when you modify your application's tables.
* Reports differences in table structure between your application's tables and audit tables.
* Disabling triggers under certain conditions.
* Flexible configuration. You can define additional columns to audit tables, for example: logging user and session IDs.

Using the audit trail you track changes made to the data of your application by the users of the application. 
Even of data that has been deleted or changed back to its original state. Also, you can track how your application manipulates data and find bugs if your application.
 

## Manual

The manual of PhpAudit can be found at [Read the Docs](https://php-audit.readthedocs.io).


## Contributing

We are looking for contributors. We can use your help for:
*	Fixing bugs and solving issues.
*	Writing documentation.
*	Developing new features.
*	Code review.
*	Implementing PhpAudit for other database systems.

You can contribute to this project in many ways:
*	Fork this project on [GitHub](https://github.com/SetBased/php-audit) and create a pull request.
*	Create an [issue](https://github.com/SetBased/php-audit/issues/new) on GitHub.
*	Asking critical questions.
*	Contacting us at [Gitter](https://gitter.im/SetBased/php-audit).


## Support
  
If you are having issues, please let us know. Contact us at [Gitter](https://gitter.im/SetBased/php-audit) or create an issue on [GitHub](https://github.com/SetBased/php-audit/issues/new).

For commercial support, please contact us at info@setbased.nl.


##  License
  
The project is licensed under the MIT license.
 
