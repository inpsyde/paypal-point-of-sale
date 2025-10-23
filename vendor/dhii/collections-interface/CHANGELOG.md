# Change log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [[*next-version*]] - YYYY-MM-DD

## [0.3.0] - 2021-10-06
Stable release.

## [0.3.0-alpha4] - 2021-03-09
### Fixed
- Order of `extends` use to cause problems with newer versions
of `psr/container` (#28).

### Changed
- QoL improvements (#28).

## [0.3.0-alpha3] - 2021-01-14
### Changed
- Supports PHP 8, and newer tools.
- Upgraded outdated configuration.

## [0.3.0-alpha2] - 2020-10-13
### Added
- `ClearableContainerInterface`.

## [0.3.0-alpha1] - 2020-09-09
### Removed
- Support for PHP 5.x.
- Obsolete dependencies, scripts and other info.
- `AddCapableInterface`, `SetCapableInterface`,  and their descendants.

### Changed
`MapFactoryInterface` now extends a new interface, leading to signature change.

### Added
- Docker configuration.
- `ContainerFactoryInterface`.
- `SetFactoryInterface`.
- `HasCapableInteface` for ISP.
- `ContainerCapableInterface` as a bridge between `HasCapableInterface`
and PSR-11.
- `WritableMapInterface` and `WritableSetInterface`.
- `MutableContainerInterface`.
- `MutableContainerInterface#unset()` can now throw `NotFoundExceptionInterface`
when unsetting non-existing key.

## [0.2] - 2019-05-10
Stable release.

## [0.2-alpha5] - 2018-04-26
### Added
- `MapFactoryInterface`.

## [0.2-alpha4] - 2018-04-09
### Fixed
- Problem #15, where `AddCapableInterface#$add()` didn't accept the item.

## [0.2-alpha3] - 2018-04-06
### Changed
- `CountableMapInterface` no longer extends `CountableSetInterface`, but still extends `CountableListInterface`.

### Added
- `SetCapableMapInterface`.

## [0.2-alpha2] - 2018-04-06
### Changed
- `SetInterface` no longer extends `HasCapableInterface`, but extends new `HasItemCapableInterface`
- `MapInterface` no longer extends `SetInterface`, but is still traversable.

### Added 
- `HasItemCapableInterface`.
- `AddCapableInterface`.
- `AddCapableSetInterface`.
- `SetCapableInterface`.

## [0.2-alpha1] - 2018-04-06
Initial version.
