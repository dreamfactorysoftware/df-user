# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

## [0.13.0] - 2017-11-03
### Changed
- Moving access check exceptions to the actual services domain
- Upgrade Swagger to OpenAPI 3.0 specification

## [0.12.0] - 2017-08-17
### Changed
- Reworked API doc usage and generation

## [0.11.0] - 2017-07-27
- Use functionality of base classes

## [0.10.0] - 2017-06-05
### Changed
- Cleanup - removal of php-utils dependency
- Fixed CSV and XML user import feature
- User and Admin now share similar email configuration

## [0.9.0] - 2017-04-21
### Added
- DF-895 Added support for username based authentication
- DF-1084 Added support for Admin User email invites

## [0.8.0] - 2017-03-03
### Changed
- Restructuring to upgrade to Laravel 5.4

### Fixed
- Fixed migrations with timestamp fields due to Laravel issue #11518 with some MySQL versions
- Cleanup of error messages and batch handling on system resources
- DF-1047 Fixed related retrieval on all verb calls

## [0.7.0] - 2017-01-16
### Fixed
- Fields confirmed and confirm_code now updated upon invitation being sent
- DF-821 Adding send_invite parameter to swagger definition

## [0.6.0] - 2016-11-17
### Added
- DF-887 Adding system/admin API doc and events for password, profile and session resources

## [0.5.0] - 2016-10-03
### Added
- DF-425 Allowing configurable role per app for open registration, OAuth, and AD/Ldap services

### Changed
- DF-742 Customizable user confirmation code length for password reset

## [0.4.1] - 2016-08-21
### Fixed
- DF-829 Fix user_custom_by_user_id relationship.

## [0.4.0] - 2016-08-21
### Changed
- General cleanup from declaration changes in df-core for service doc and providers

## [0.3.1] - 2016-07-08
### Changed
- General cleanup from declaration changes in df-core

## [0.3.0] - 2016-05-27
### Changed
- Moved seeding functionality to service provider to adhere to df-core changes

## [0.2.4] - 2016-04-21
### Fixed
- Swagger ordering

## [0.2.3] - 2016-03-08
### Fixed
- Swagger documentation update to pass validation
- Setting role properly for newly registered user using open registration

## [0.2.2] - 2016-02-08
### Fixed
- Fixed user search filter on 'Users' tab in admin app

[Unreleased]: https://github.com/dreamfactorysoftware/df-user/compare/0.13.0...HEAD
[0.13.0]: https://github.com/dreamfactorysoftware/df-user/compare/0.12.0...0.13.0
[0.12.0]: https://github.com/dreamfactorysoftware/df-user/compare/0.11.0...0.12.0
[0.11.0]: https://github.com/dreamfactorysoftware/df-user/compare/0.10.0...0.11.0
[0.10.0]: https://github.com/dreamfactorysoftware/df-user/compare/0.9.0...0.10.0
[0.9.0]: https://github.com/dreamfactorysoftware/df-user/compare/0.8.0...0.9.0
[0.8.0]: https://github.com/dreamfactorysoftware/df-user/compare/0.7.0...0.8.0
[0.7.0]: https://github.com/dreamfactorysoftware/df-user/compare/0.6.0...0.7.0
[0.6.0]: https://github.com/dreamfactorysoftware/df-user/compare/0.5.0...0.6.0
[0.5.0]: https://github.com/dreamfactorysoftware/df-user/compare/0.4.1...0.5.0
[0.4.1]: https://github.com/dreamfactorysoftware/df-user/compare/0.4.0...0.4.1
[0.4.0]: https://github.com/dreamfactorysoftware/df-user/compare/0.3.1...0.4.0
[0.3.1]: https://github.com/dreamfactorysoftware/df-user/compare/0.3.0...0.3.1
[0.3.0]: https://github.com/dreamfactorysoftware/df-user/compare/0.2.4...0.3.0
[0.2.4]: https://github.com/dreamfactorysoftware/df-user/compare/0.2.3...0.2.4
[0.2.3]: https://github.com/dreamfactorysoftware/df-user/compare/0.2.2...0.2.3
[0.2.2]: https://github.com/dreamfactorysoftware/df-user/compare/0.2.1...0.2.2
[0.2.1]: https://github.com/dreamfactorysoftware/df-user/compare/0.2.0...0.2.1
[0.2.0]: https://github.com/dreamfactorysoftware/df-user/compare/0.1.2...0.2.0
