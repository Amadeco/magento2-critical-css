# Changelog

All notable changes to the `M2Boilerplate_CriticalCss` module will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [2.3.3] - 2026-02-17

### Changed

* **Plugin Architecture Refactoring**:
* Refactored `M2Boilerplate\CriticalCss\Plugin\AsyncCssPlugin` to remove inheritance from the core Magento class, resolving the "Double Instantiation" anti-pattern and aligning with Magento 2.4.8 interceptor standards.
* Manually implemented the CSS `rel="preload"` replacement logic within the plugin to ensure seamless functionality while the native plugin is disabled via `di.xml`.

* **Provider Modernization**:
* Refactored `ContactProvider`, `CustomerProvider`, and `DefaultProvider` to utilize **PHP 8.3 Constructor Property Promotion** and `readonly` properties.
* Optimized `CustomerProvider` to use the native `Magento\Customer\Model\Url` service contract, ensuring architectural consistency and respect for system-wide customer route configurations.

### Fixed

* **XML Syntax Error**: Corrected a malformed closing tag in `etc/config.xml` that prevented proper loading of default configuration values.
* **Configuration Logic**: Resolved a bug in `AsyncCssPlugin` where an undefined method `isCssCriticalEnabled` was called; it now correctly utilizes the `isEnabled()` method from the `Config` service.
* **Default Fallback**: Re-enabled and modernized the `DefaultProvider` to serve as a robust catch-all fallback for generic page layouts.

---

### Next Step for Amadeco

Would you like me to generate the **Data Patch** to migrate the existing configuration values from the old `dev/css` paths to the new `m2bp_critical_css` section to ensure zero downtime during deployment?

---

## [2.3.2] - 2026-02-16

### Added

* **CSS Minification**: Integrated the `--minify` flag into the `critical` binary execution to compress inline CSS, reducing the document size and improving Time to First Byte (TTFB).
* **Aggressive Default Filters**: Implemented automatic exclusion of `print` media queries by default to ensure only visual-critical, above-the-fold styles are included.

### Changed

* **Refactoring**: Modernized `M2Boilerplate\CriticalCss\Provider\CmsPageProvider` with PHP 8.3 features, including constructor property promotion and strict typing.

### Removed

* **Clean Code**: Removed dead code (commented-out logic) and unused dependencies (`PageRepositoryInterface`, `SearchCriteriaBuilder`, `PageHelper`) from `CmsPageProvider` to improve maintainability and performance.

### Optimized

* **Frontend Performance**: Reduced the payload of Critical CSS by filtering out non-essential print rules, leading to improved **First Contentful Paint (FCP)** and **Largest Contentful Paint (LCP)** scores.

---

## [2.3.1] - 2026-02-16

### Changed

* **Plugin Refactoring**: Optimized `M2Boilerplate\CriticalCss\Plugin\CriticalCss` to align with PHP 8.3 standards.
* **Immutability**: Transitioned all injected dependencies to `readonly` promoted properties within the constructor to ensure service integrity.
* **Type Safety**: Enforced strict scalar and object typing for all method parameters and return values.

### Removed

* **Unused Dependencies**: Excised the `FlagManager` and `GenerateCommand` imports from the plugin class to reduce memory overhead and adhere to the **KISS** (Keep It Simple, Stupid) principle.

### Documentation

* **PHPDoc Standards**: Updated the class and method documentation to full English PHPDoc standards, explicitly describing the interception logic for the `afterGetCriticalCssData` method.

---

## [2.3.0] - 2026-02-16

### Added

* **Strict Typing Implementation**: Added `declare(strict_types=1);` to all core service and provider files to ensure engine-level type safety.
* **Modern PHP 8.3 Syntax**: Leveraged **Constructor Property Promotion** across all refactored classes to reduce boilerplate code and improve readability.
* **Readonly Properties**: Applied `readonly` modifiers to injected dependencies in services like `Config` and `ProcessContext` to enforce immutability.

### Changed

* **Provider Container Optimization**:
* Refactored `M2Boilerplate\CriticalCss\Provider\Container` to use **Memoization** for sorted provider retrieval.
* Fixed a critical bug where `usort` destroyed associative keys; the container now maintains an internal map for  lookups by name while providing a sorted list for iteration.
* Updated sorting logic to use the PHP 8 **Spaceship Operator (`<=>`)**.


* **Controller Modernization**:
* Refactored `M2Boilerplate\CriticalCss\Controller\CriticalCss\DefaultAction` to implement `Magento\Framework\App\Action\HttpGetActionInterface`.
* Removed deprecated inheritance from `Magento\Framework\App\Action\Action` to comply with Magento 2.4.8 architectural standards.


* **Service Refinement**:
* Updated `Config` service with strict return type hinting (`string`, `int`, `bool`, `array`) and enhanced null safety for HTTP authentication credentials.
* Refactored `ProcessContext` into a strict Data Transfer Object (DTO) with promoted properties.


* **Provider Standardization**:
* Updated `ProviderInterface` with strict parameter and return type hints.
* Refactored `CategoryProvider` and `CatalogSearchProvider` to use PHP 8.3 syntax and optimized collection loading.

### Fixed

* **Memory Management**: Enforced strict `COLLECTION_LIMIT` constants in the `CategoryProvider` to prevent Out-Of-Memory (OOM) errors during the generation of URLs for large catalogs.
* **Type Safety**: Resolved potential type-juggling issues in `Config` by ensuring configuration values are explicitly cast to expected types before returning.

### Documentation

* **PHPDoc Overhaul**: Added comprehensive English PHPDoc blocks to all refactored classes, methods, and properties, following Magento 2 coding standards.

---

## [2.2.0] - 2024-05-20

### Security
- **Log Sanitization:** Implemented a `sanitizeCommand` method in `ProcessManager` to strip sensitive credentials (passwords) from logs before writing to `var/log/critical-css.log`.
- **Command Injection Prevention:** Enforced strict typing on all command arguments to prevent type juggling vulnerabilities.
- **Object Manager Removal:** Removed forbidden direct usage of `ObjectManager` in `GenerateCommand`. Implemented `ConsoleLoggerFactory` to strictly handle runtime dependency injection for Console Output.

### Changed
- **PHP 8.3 Upgrade:**
    - Updated entire codebase to PHP 8.3 standards.
    - Implemented `declare(strict_types=1)` in all files.
    - Refactored all classes to use **Constructor Property Promotion**.
    - Added explicit return types and property type hinting throughout.
- **Async CSS Parsing:** Refactored `AsyncCssPlugin` to use robust Regular Expressions (`preg_match`) for detecting Critical CSS blocks, replacing the fragile `strpos/substr` HTML parsing logic.
- **Service Isolation:** Refactored `CssProcessor` to strictly require `StoreInterface` for correct base URL resolution during CSS post-processing.
- **Code Style:** Applied PSR-12 coding standards and comprehensive English PHPDoc documentation across all refactored files.

### Fixed
- **Out of Memory (OOM) Protection:**
    - **CategoryProvider:** Implemented a hard limit (500) and sorting strategy (by children count/level) to prevent loading the entire category tree into memory.
    - **ProductProvider:** Implemented a safety limit (20) and optimized collection loading to prevent OOM errors on large catalogs.
- **Legacy Authentication:** Refactored `CriticalCss` service to safely handle legacy command-line arguments while adhering to modern type safety standards.
- **Process Management:** Optimized the `ProcessManager` execution loop to improve readability and maintainability.

### Removed
- Removed unused and commented-out code (dead code) from `GenerateCommand` and `ProcessManager` to adhere to KISS principles.
