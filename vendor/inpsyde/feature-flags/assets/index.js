(function webpackUniversalModuleDefinition(root, factory) {
	if(typeof exports === 'object' && typeof module === 'object')
		module.exports = factory();
	else if(typeof define === 'function' && define.amd)
		define("@inpsyde/feature-flags", [], factory);
	else if(typeof exports === 'object')
		exports["@inpsyde/feature-flags"] = factory();
	else
		root["@inpsyde/feature-flags"] = factory();
})(this, () => {
return /******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./resources/components/FeatureFlagIndicator.js"
/*!******************************************************!*\
  !*** ./resources/components/FeatureFlagIndicator.js ***!
  \******************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   FeatureFlagIndicator: () => (/* binding */ FeatureFlagIndicator)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);



/**
 * Displays the current project and feature flag selection status in the block editor.
 *
 * @param {Object} props
 * @param {string} props.project Selected project name.
 * @param {string} props.flag Selected feature flag name.
 * @param {boolean} props.enabled Whether the selected flag is enabled.
 * @return {JSX.Element}
 */
function FeatureFlagIndicator({
  project,
  flag,
  enabled
}) {
  if (!project) {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "feature-flag-indicator"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "feature-flag-indicator__no-project"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('No project selected', 'feature-flags-block')));
  }
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "feature-flag-indicator"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "feature-flag-indicator__info"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "feature-flag-indicator__project"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Project:', 'feature-flags-block'), " ", (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, project)), flag ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: `feature-flag-indicator__flag ${enabled ? 'enabled' : 'disabled'}`
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Flag:', 'feature-flags-block'), " ", (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, flag), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "feature-flag-indicator__status"
  }, "(", enabled ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Enabled', 'feature-flags-block') : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Disabled', 'feature-flags-block'), ")")) : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "feature-flag-indicator__no-flag"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('No feature flag selected', 'feature-flags-block'))));
}

/***/ },

/***/ "./resources/components/FeatureFlagSelect.js"
/*!***************************************************!*\
  !*** ./resources/components/FeatureFlagSelect.js ***!
  \***************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   FeatureFlagSelect: () => (/* binding */ FeatureFlagSelect)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _hooks__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../hooks */ "./resources/hooks/index.js");






/**
 * Combobox for selecting a feature flag with search/filter support.
 *
 * @param {Object} props
 * @param {string} props.project Project identifier to fetch flags for.
 * @param {string} props.value Currently selected flag value.
 * @param {Function} props.onChange Callback when selection changes.
 * @return {JSX.Element}
 */
function FeatureFlagSelect({
  project,
  value,
  onChange
}) {
  const {
    flags,
    isLoading,
    error
  } = (0,_hooks__WEBPACK_IMPORTED_MODULE_4__.useFeatureFlags)(project);
  const [filteredFlags, setFilteredFlags] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)([]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useEffect)(() => {
    setFilteredFlags(flags);
  }, [flags]);
  const handleFilterChange = inputValue => {
    if (!inputValue) {
      setFilteredFlags(flags);
      return;
    }
    const filtered = flags.filter(option => option.label.toLowerCase().includes(inputValue.toLowerCase()) || option.value.toLowerCase().includes(inputValue.toLowerCase()) || option.description && option.description.toLowerCase().includes(inputValue.toLowerCase()));
    setFilteredFlags(filtered);
  };
  const selectedDetails = flags.find(flag => flag.value === value);
  if (!project) {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Notice, {
      status: "info",
      isDismissible: false
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Please select a project first to load available feature flags.', 'feature-flags-block'));
  }
  if (isLoading) {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "feature-flag-select__loading"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Spinner, null), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Loading feature flags...', 'feature-flags-block')));
  }
  if (error) {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Notice, {
      status: "error",
      isDismissible: false
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Error loading feature flags: ', 'feature-flags-block'), " ", error);
  }
  if (flags.length === 0) {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Notice, {
      status: "warning",
      isDismissible: false
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('No feature flags found in the selected project.', 'feature-flags-block'));
  }
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.ComboboxControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Select Feature Flag', 'feature-flags-block'),
    value: value,
    onChange: onChange,
    onFilterValueChange: handleFilterChange,
    options: filteredFlags,
    help: selectedDetails?.description || (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Choose which feature flag to check against', 'feature-flags-block'),
    placeholder: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Search for a feature flag...', 'feature-flags-block')
  });
}

/***/ },

/***/ "./resources/components/FeatureItem.js"
/*!*********************************************!*\
  !*** ./resources/components/FeatureItem.js ***!
  \*********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   FeatureItem: () => (/* binding */ FeatureItem)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__);




/**
 * Displays a single feature flag with its name, description, and status.
 *
 * @param {Object} props
 * @param {string} props.name Feature flag name.
 * @param {string} props.label Display label for the feature flag.
 * @param {string} props.description Feature flag description.
 * @param {boolean} props.enabled Whether the feature flag is enabled.
 * @param {Function} props.onClick Optional click handler.
 * @param {boolean} props.selected Whether this item is selected.
 * @return {JSX.Element}
 */
function FeatureItem({
  name,
  label,
  description,
  enabled,
  onClick,
  selected = false
}) {
  const statusClass = enabled ? 'enabled' : 'disabled';
  const statusText = enabled ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Enabled', 'feature-flags-block') : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Disabled', 'feature-flags-block');
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.Card, {
    size: "small"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.CardHeader, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.__experimentalHStack, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.__experimentalText, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, label || name)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.ToggleControl, {
    label: statusText,
    checked: enabled,
    disabled: true
  }))));
}

/***/ },

/***/ "./resources/components/FeatureList.js"
/*!*********************************************!*\
  !*** ./resources/components/FeatureList.js ***!
  \*********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   FeatureList: () => (/* binding */ FeatureList)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../utils/api */ "./resources/utils/api.js");
/* harmony import */ var _FeatureItem__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./FeatureItem */ "./resources/components/FeatureItem.js");







/**
 * Displays a list of feature flags for a given project.
 *
 * @param {Object} props
 * @param {string} props.project Project identifier to fetch flags for.
 * @param {string} props.selectedFlag Currently selected flag value.
 * @param {Function} props.onSelect Callback when a flag is selected.
 * @return {JSX.Element}
 */
function FeatureList({
  project,
  selectedFlag,
  onSelect
}) {
  const [flags, setFlags] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)([]);
  const [isLoading, setIsLoading] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)(false);
  const [error, setError] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)(null);
  const enabledCount = flags.filter(flag => flag.enabled).length;
  const disabledCount = flags.length - enabledCount;
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useEffect)(() => {
    if (!project) {
      setFlags([]);
      return;
    }
    const loadFlags = async () => {
      try {
        setIsLoading(true);
        setError(null);
        const flagOptions = await (0,_utils_api__WEBPACK_IMPORTED_MODULE_4__.fetchFeatureFlags)(project);
        setFlags(flagOptions);
      } catch (err) {
        console.error('Error fetching feature flags:', err);
        setError(err.message);
        setFlags([]);
      } finally {
        setIsLoading(false);
      }
    };
    loadFlags();
  }, [project]);
  if (!project) {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Notice, {
      status: "info",
      isDismissible: false
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Please select a project to view feature flags.', 'feature-flags-block'));
  }
  if (isLoading) {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "feature-list__loading"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Spinner, null), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Loading feature flags...', 'feature-flags-block')));
  }
  if (error) {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Notice, {
      status: "error",
      isDismissible: false
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Error loading feature flags: ', 'feature-flags-block'), " ", error);
  }
  if (flags.length === 0) {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Notice, {
      status: "warning",
      isDismissible: false
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('No feature flags found in this project.', 'feature-flags-block'));
  }
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.__experimentalHStack, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.__experimentalText, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, flags.length), " ", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('total flags', 'feature-flags-block')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.__experimentalText, {
    className: "feature-flags-admin__count--enabled"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, enabledCount), " ", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('enabled', 'feature-flags-block')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.__experimentalText, {
    className: "feature-flags-admin__count--disabled"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, disabledCount), " ", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('disabled', 'feature-flags-block'))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.__experimentalSpacer, null), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.__experimentalVStack, null, flags.map(flag => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_FeatureItem__WEBPACK_IMPORTED_MODULE_5__.FeatureItem, {
    key: flag.value,
    name: flag.value,
    label: flag.label,
    description: flag.description,
    enabled: flag.enabled,
    selected: selectedFlag === flag.value,
    onClick: onSelect ? () => onSelect(flag.value) : undefined
  }))));
}

/***/ },

/***/ "./resources/components/ProjectList.js"
/*!*********************************************!*\
  !*** ./resources/components/ProjectList.js ***!
  \*********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   ProjectList: () => (/* binding */ ProjectList)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _FeatureList__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./FeatureList */ "./resources/components/FeatureList.js");
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../utils/api */ "./resources/utils/api.js");







/**
 * Displays a single project item.
 *
 * @param {Object} props
 * @param {string} props.name Project name/value.
 * @param {string} props.label Display label for the project.
 * @param {string} props.description Project description.
 * @param {Function} props.onClick Optional click handler.
 * @param {boolean} props.selected Whether this item is selected.
 * @return {JSX.Element}
 */
function ProjectItem({
  name,
  label,
  description,
  onClick,
  selected = false
}) {
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Card, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.CardHeader, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.__experimentalVStack, {
    spacing: 1
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.__experimentalHeading, {
    level: 3
  }, label || name), description && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.__experimentalText, {
    variant: "muted"
  }, description))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.CardBody, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_FeatureList__WEBPACK_IMPORTED_MODULE_4__.FeatureList, {
    project: name
  })));
}

/**
 * Displays a list of available projects.
 *
 * @param {Object} props
 * @param {string} props.selectedProject Currently selected project value.
 * @param {Function} props.onSelect Callback when a project is selected.
 * @return {JSX.Element}
 */
function ProjectList({
  selectedProject,
  onSelect
}) {
  const [projects, setProjects] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)([]);
  const [isLoading, setIsLoading] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)(true);
  const [error, setError] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)(null);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useEffect)(() => {
    const loadProjects = async () => {
      try {
        setIsLoading(true);
        setError(null);
        const projectOptions = await (0,_utils_api__WEBPACK_IMPORTED_MODULE_5__.fetchProjects)();
        setProjects(projectOptions);
      } catch (err) {
        console.error('Error fetching projects:', err);
        setError(err.message);
        setProjects([]);
      } finally {
        setIsLoading(false);
      }
    };
    loadProjects();
  }, []);
  if (isLoading) {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "project-list__loading"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Spinner, null), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Loading projects...', 'feature-flags-block')));
  }
  if (error) {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Notice, {
      status: "error",
      isDismissible: false
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Error loading projects: ', 'feature-flags-block'), " ", error);
  }
  if (projects.length === 0) {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Notice, {
      status: "warning",
      isDismissible: false
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('No projects found.', 'feature-flags-block'));
  }
  return projects.map(project => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    key: project.value
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(ProjectItem, {
    name: project.value,
    label: project.label,
    description: project.description,
    selected: selectedProject === project.value,
    onClick: onSelect ? () => onSelect(project.value) : undefined
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.__experimentalSpacer, null)));
}

/***/ },

/***/ "./resources/components/ProjectSelect.js"
/*!***********************************************!*\
  !*** ./resources/components/ProjectSelect.js ***!
  \***********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   ProjectSelect: () => (/* binding */ ProjectSelect)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _hooks__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../hooks */ "./resources/hooks/index.js");






/**
 * Combobox for selecting a project with search/filter support.
 *
 * @param {Object} props
 * @param {string} props.value Currently selected project value.
 * @param {Function} props.onChange Callback when selection changes.
 * @return {JSX.Element}
 */
function ProjectSelect({
  value,
  onChange
}) {
  const {
    projects,
    isLoading,
    error
  } = (0,_hooks__WEBPACK_IMPORTED_MODULE_4__.useProjects)();
  const [filteredProjects, setFilteredProjects] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useState)([]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_2__.useEffect)(() => {
    setFilteredProjects(projects);
  }, [projects]);
  const handleFilterChange = inputValue => {
    if (!inputValue) {
      setFilteredProjects(projects);
      return;
    }
    const filtered = projects.filter(option => option.label.toLowerCase().includes(inputValue.toLowerCase()) || option.value.toLowerCase().includes(inputValue.toLowerCase()) || option.description && option.description.toLowerCase().includes(inputValue.toLowerCase()));
    setFilteredProjects(filtered);
  };
  const selectedDetails = projects.find(project => project.value === value);
  if (isLoading) {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "project-select__loading"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Spinner, null), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Loading projects...', 'feature-flags-block')));
  }
  if (error) {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Notice, {
      status: "error",
      isDismissible: false
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Error loading projects: ', 'feature-flags-block'), " ", error);
  }
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.ComboboxControl, {
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Select Project', 'feature-flags-block'),
    value: value,
    onChange: onChange,
    onFilterValueChange: handleFilterChange,
    options: filteredProjects,
    help: selectedDetails?.description || (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Choose which project to load feature flags from', 'feature-flags-block'),
    placeholder: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Search for a project...', 'feature-flags-block')
  });
}

/***/ },

/***/ "./resources/components/SelectionDetails.js"
/*!**************************************************!*\
  !*** ./resources/components/SelectionDetails.js ***!
  \**************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   SelectionDetails: () => (/* binding */ SelectionDetails)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);



/**
 * Displays details about the selected project and feature flag.
 *
 * @param {Object} props
 * @param {Object} props.project Selected project details.
 * @param {string} props.project.label Project label.
 * @param {string} props.project.description Project description.
 * @param {Object} props.flag Selected feature flag details.
 * @param {string} props.flag.label Flag label.
 * @param {string} props.flag.description Flag description.
 * @param {boolean} props.flag.enabled Whether the flag is enabled.
 * @return {JSX.Element|null}
 */
function SelectionDetails({
  project,
  flag
}) {
  if (!project && !flag) {
    return null;
  }
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "selection-details"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Selection Details', 'feature-flags-block')), project && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "selection-details__project"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Project:', 'feature-flags-block')), " ", project.label), project.description && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Description:', 'feature-flags-block')), " ", project.description)), flag && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "selection-details__flag"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Flag:', 'feature-flags-block')), " ", flag.label), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Status:', 'feature-flags-block')), ' ', flag.enabled ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Enabled', 'feature-flags-block') : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Disabled', 'feature-flags-block')), flag.description && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("strong", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Description:', 'feature-flags-block')), " ", flag.description)));
}

/***/ },

/***/ "./resources/components/index.js"
/*!***************************************!*\
  !*** ./resources/components/index.js ***!
  \***************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   FeatureFlagIndicator: () => (/* reexport safe */ _FeatureFlagIndicator__WEBPACK_IMPORTED_MODULE_5__.FeatureFlagIndicator),
/* harmony export */   FeatureFlagSelect: () => (/* reexport safe */ _FeatureFlagSelect__WEBPACK_IMPORTED_MODULE_4__.FeatureFlagSelect),
/* harmony export */   FeatureItem: () => (/* reexport safe */ _FeatureItem__WEBPACK_IMPORTED_MODULE_3__.FeatureItem),
/* harmony export */   FeatureList: () => (/* reexport safe */ _FeatureList__WEBPACK_IMPORTED_MODULE_2__.FeatureList),
/* harmony export */   ProjectList: () => (/* reexport safe */ _ProjectList__WEBPACK_IMPORTED_MODULE_0__.ProjectList),
/* harmony export */   ProjectSelect: () => (/* reexport safe */ _ProjectSelect__WEBPACK_IMPORTED_MODULE_1__.ProjectSelect),
/* harmony export */   SelectionDetails: () => (/* reexport safe */ _SelectionDetails__WEBPACK_IMPORTED_MODULE_6__.SelectionDetails)
/* harmony export */ });
/* harmony import */ var _ProjectList__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./ProjectList */ "./resources/components/ProjectList.js");
/* harmony import */ var _ProjectSelect__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./ProjectSelect */ "./resources/components/ProjectSelect.js");
/* harmony import */ var _FeatureList__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./FeatureList */ "./resources/components/FeatureList.js");
/* harmony import */ var _FeatureItem__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./FeatureItem */ "./resources/components/FeatureItem.js");
/* harmony import */ var _FeatureFlagSelect__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./FeatureFlagSelect */ "./resources/components/FeatureFlagSelect.js");
/* harmony import */ var _FeatureFlagIndicator__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./FeatureFlagIndicator */ "./resources/components/FeatureFlagIndicator.js");
/* harmony import */ var _SelectionDetails__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./SelectionDetails */ "./resources/components/SelectionDetails.js");








/***/ },

/***/ "./resources/hooks/index.js"
/*!**********************************!*\
  !*** ./resources/hooks/index.js ***!
  \**********************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   useAsync: () => (/* reexport safe */ _useAsync__WEBPACK_IMPORTED_MODULE_0__.useAsync),
/* harmony export */   useFeatureFlags: () => (/* reexport safe */ _useFeatureFlags__WEBPACK_IMPORTED_MODULE_2__.useFeatureFlags),
/* harmony export */   useProjects: () => (/* reexport safe */ _useProjects__WEBPACK_IMPORTED_MODULE_1__.useProjects)
/* harmony export */ });
/* harmony import */ var _useAsync__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./useAsync */ "./resources/hooks/useAsync.js");
/* harmony import */ var _useProjects__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./useProjects */ "./resources/hooks/useProjects.js");
/* harmony import */ var _useFeatureFlags__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./useFeatureFlags */ "./resources/hooks/useFeatureFlags.js");




/***/ },

/***/ "./resources/hooks/useAsync.js"
/*!*************************************!*\
  !*** ./resources/hooks/useAsync.js ***!
  \*************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   useAsync: () => (/* binding */ useAsync)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);


/**
 * Generic hook for handling async operations with loading and error states.
 *
 * @param {Function} asyncFn The async function to execute.
 * @param {Array} deps Dependencies array that triggers re-fetch when changed.
 * @param {Object} options Configuration options.
 * @param {boolean} options.immediate Whether to execute immediately (default: true).
 * @param {*} options.initialData Initial data value (default: []).
 * @return {{data: *, isLoading: boolean, error: string|null, refetch: Function}}
 */
function useAsync(asyncFn, deps = [], {
  immediate = true,
  initialData = []
} = {}) {
  const [data, setData] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(initialData);
  const [isLoading, setIsLoading] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(immediate);
  const [error, setError] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(null);
  const execute = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useCallback)(async () => {
    try {
      setIsLoading(true);
      setError(null);
      const result = await asyncFn();
      setData(result);
      return result;
    } catch (err) {
      console.error('useAsync error:', err);
      setError(err.message);
      setData(initialData);
      return initialData;
    } finally {
      setIsLoading(false);
    }
  }, [asyncFn, initialData]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    if (immediate) {
      execute();
    }
  }, [...deps, immediate]);
  return {
    data,
    isLoading,
    error,
    refetch: execute
  };
}

/***/ },

/***/ "./resources/hooks/useFeatureFlags.js"
/*!********************************************!*\
  !*** ./resources/hooks/useFeatureFlags.js ***!
  \********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   useFeatureFlags: () => (/* binding */ useFeatureFlags)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utils/api */ "./resources/utils/api.js");



/**
 * Hook for fetching and managing feature flags for a project.
 *
 * @param {string} project Project identifier.
 * @return {{flags: Array, isLoading: boolean, error: string|null, refetch: Function}}
 */
function useFeatureFlags(project) {
  const [flags, setFlags] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)([]);
  const [isLoading, setIsLoading] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(false);
  const [error, setError] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(null);
  const fetchFlags = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useCallback)(async () => {
    if (!project) {
      setFlags([]);
      return [];
    }
    try {
      setIsLoading(true);
      setError(null);
      const result = await (0,_utils_api__WEBPACK_IMPORTED_MODULE_1__.fetchFeatureFlags)(project);
      setFlags(result);
      return result;
    } catch (err) {
      console.error('useFeatureFlags error:', err);
      setError(err.message);
      setFlags([]);
      return [];
    } finally {
      setIsLoading(false);
    }
  }, [project]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    fetchFlags();
  }, [fetchFlags]);
  return {
    flags,
    isLoading,
    error,
    refetch: fetchFlags
  };
}

/***/ },

/***/ "./resources/hooks/useProjects.js"
/*!****************************************!*\
  !*** ./resources/hooks/useProjects.js ***!
  \****************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   useProjects: () => (/* binding */ useProjects)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../utils/api */ "./resources/utils/api.js");
/* harmony import */ var _useAsync__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./useAsync */ "./resources/hooks/useAsync.js");




/**
 * Hook for fetching and managing projects.
 *
 * @return {{projects: Array, isLoading: boolean, error: string|null, refetch: Function}}
 */
function useProjects() {
  const fetchFn = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useCallback)(() => (0,_utils_api__WEBPACK_IMPORTED_MODULE_1__.fetchProjects)(), []);
  const {
    data: projects,
    isLoading,
    error,
    refetch
  } = (0,_useAsync__WEBPACK_IMPORTED_MODULE_2__.useAsync)(fetchFn, []);
  return {
    projects,
    isLoading,
    error,
    refetch
  };
}

/***/ },

/***/ "./resources/utils/api.js"
/*!********************************!*\
  !*** ./resources/utils/api.js ***!
  \********************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   fetchFeatureFlags: () => (/* binding */ fetchFeatureFlags),
/* harmony export */   fetchProjects: () => (/* binding */ fetchProjects),
/* harmony export */   getFlagState: () => (/* binding */ getFlagState)
/* harmony export */ });
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0__);


/**
 * Fetches available projects from the abilities API.
 *
 * @return {Promise<Array<{value: string, label: string, description: string}>>} Array of project options.
 */
async function fetchProjects() {
  const response = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0___default()({
    path: '/wp-abilities/v1/abilities/syde/get-projects/run',
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({})
  });
  return response.map(project => ({
    value: project.name || project.id,
    label: project.label || project.name || project.id,
    description: project.description || ''
  }));
}

/**
 * Fetches feature flags for a specific project from the abilities API.
 *
 * @param {string} project The project identifier.
 * @return {Promise<Array<{value: string, label: string, description: string, enabled: boolean}>>} Array of feature flag options.
 */
async function fetchFeatureFlags(project) {
  const response = await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0___default()({
    path: '/wp-abilities/v1/abilities/syde/get-flags/run',
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      input: {
        project
      }
    })
  });
  return response.map(flag => ({
    value: flag.name || flag.id,
    label: flag.label || flag.name || flag.id,
    description: flag.description || '',
    enabled: flag.enabled || false
  }));
}

/**
 * Gets the state of a specific feature flag.
 *
 * @param {string} project The project identifier.
 * @param {string} flag The feature flag name.
 * @return {Promise<boolean>} Whether the flag is enabled.
 */
async function getFlagState(project, flag) {
  var _found$enabled;
  const flags = await fetchFeatureFlags(project);
  const found = flags.find(f => f.value === flag);
  return (_found$enabled = found?.enabled) !== null && _found$enabled !== void 0 ? _found$enabled : false;
}

/***/ },

/***/ "@wordpress/api-fetch"
/*!**********************************!*\
  !*** external ["wp","apiFetch"] ***!
  \**********************************/
(module) {

module.exports = window["wp"]["apiFetch"];

/***/ },

/***/ "@wordpress/components"
/*!************************************!*\
  !*** external ["wp","components"] ***!
  \************************************/
(module) {

module.exports = window["wp"]["components"];

/***/ },

/***/ "@wordpress/element"
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
(module) {

module.exports = window["wp"]["element"];

/***/ },

/***/ "@wordpress/i18n"
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
(module) {

module.exports = window["wp"]["i18n"];

/***/ },

/***/ "react"
/*!************************!*\
  !*** external "React" ***!
  \************************/
(module) {

module.exports = window["React"];

/***/ }

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Check if module exists (development only)
/******/ 		if (__webpack_modules__[moduleId] === undefined) {
/******/ 			var e = new Error("Cannot find module '" + moduleId + "'");
/******/ 			e.code = 'MODULE_NOT_FOUND';
/******/ 			throw e;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!****************************!*\
  !*** ./resources/index.js ***!
  \****************************/
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   FeatureFlagIndicator: () => (/* reexport safe */ _components__WEBPACK_IMPORTED_MODULE_0__.FeatureFlagIndicator),
/* harmony export */   FeatureFlagSelect: () => (/* reexport safe */ _components__WEBPACK_IMPORTED_MODULE_0__.FeatureFlagSelect),
/* harmony export */   FeatureItem: () => (/* reexport safe */ _components__WEBPACK_IMPORTED_MODULE_0__.FeatureItem),
/* harmony export */   FeatureList: () => (/* reexport safe */ _components__WEBPACK_IMPORTED_MODULE_0__.FeatureList),
/* harmony export */   ProjectList: () => (/* reexport safe */ _components__WEBPACK_IMPORTED_MODULE_0__.ProjectList),
/* harmony export */   ProjectSelect: () => (/* reexport safe */ _components__WEBPACK_IMPORTED_MODULE_0__.ProjectSelect),
/* harmony export */   SelectionDetails: () => (/* reexport safe */ _components__WEBPACK_IMPORTED_MODULE_0__.SelectionDetails),
/* harmony export */   fetchFeatureFlags: () => (/* reexport safe */ _utils_api__WEBPACK_IMPORTED_MODULE_2__.fetchFeatureFlags),
/* harmony export */   fetchProjects: () => (/* reexport safe */ _utils_api__WEBPACK_IMPORTED_MODULE_2__.fetchProjects),
/* harmony export */   useAsync: () => (/* reexport safe */ _hooks__WEBPACK_IMPORTED_MODULE_1__.useAsync),
/* harmony export */   useFeatureFlags: () => (/* reexport safe */ _hooks__WEBPACK_IMPORTED_MODULE_1__.useFeatureFlags),
/* harmony export */   useProjects: () => (/* reexport safe */ _hooks__WEBPACK_IMPORTED_MODULE_1__.useProjects)
/* harmony export */ });
/* harmony import */ var _components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./components */ "./resources/components/index.js");
/* harmony import */ var _hooks__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./hooks */ "./resources/hooks/index.js");
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./utils/api */ "./resources/utils/api.js");
/**
 * @inpsyde/feature-flags
 *
 * Main entry point for the feature flags JS library.
 * Exports all public components, hooks, and utilities.
 */

// Components


// Hooks


// API utilities

})();

/******/ 	return __webpack_exports__;
/******/ })()
;
});
//# sourceMappingURL=index.js.map