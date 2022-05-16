# Humsci Basic Pull Request Checklist

**When reviewing a pull request, please check for the following:**

## Code
#### Code Quality
- [ ] Is there anything in this code that would be hidden or hard to discover through the UI?
- [ ] Are there any code smells? https://blog.codinghorror.com/code-smells/

#### Tests
- [ ] Unit test provided
- [ ] Does this require a behat test?

#### Documentation
- [ ] Are there enough comments inline with the code?
- [ ] Is there more documentation needed?

#### Accessibility
Adhering to these general rules will save us time fixing bugs in the future related to accessibility:

- [ ] Semantic HTML is used (use more semantic elements over divs)
- [ ] ARIA is only used when you have to (if there is another way to do things using native elements and attributes, do so)

#### Security
- [ ] Do forms need to be sanitized?
- [ ] Any obvious security flaws or potential holes?

#### Localization/Internationalization
- [ ] Is all UI content (labels and other strings, but _NOT_ user generated content) translated?
  - Make use of the `trans` or `t` filter

## Accessibility
Use this checklist when user-facing (non-admin) code is added.

We will divide the work we do as part of our accessibility checklist into a 25/75 ratio of automated/manual testing, or as close as we can get.

**Background on Automated/Manual Testing Approach**
The [SOAP office](https://soap.stanford.edu/) notes:

> Automated tests...only cover roughly 30% of accessibility requirements. The remaining 70% [of tests] require human judgement in the form of: functional tests; visual inspections; and usability tests.

For this reason, we will divide the work we do as part of our accessibility check for all PRs into a ratio that weights manual testing as more significant than automated testing. For now we will give around 25% effort/signficance towards using automated testing, and 75% towards using manual testing.

### Automated Testing (25%)
In order to get a high level sense of any issues that might exist, such as missing alt text, missing link text, and color contrast issues, we can use an automated tool during and after development.

- [ ] Employ the use of [Axe](https://www.deque.com/axe/), [Wave](https://wave.webaim.org/), or [AMP](https://wave.webaim.org/) based on which automated tool we prefer to analyze a page, or component.
  - Use these tools to investigate for errors. As automated tools, they may find a violation with something that is not completely correct based on context. Use your subjective judgement to decide if it’s really a violation or not. Please record notes on this.

### Manual Testing (75%)

#### Keyboard Testing
While keyboard testing, check for the following:

1. Are you able you tab to elements as they appear in the visual order? (Forms, buttons, links?)
1. Can you operate any clickable or JS functionality using the keyboard? (Using Spacebar/Enter keys)

- [ ] Keyboard-only navigate on Chrome
- [ ] Keyboard-only navigate on Safari
- [ ] Keyboard-only navigate on Firefox

#### Screen Readers
- [ ] VoiceOver Testing on Chrome
- [ ] VoiceOver Testing on Safari

## Quality Assurance
Use this checklist when a new, user-facing, visual feature is added:

### Gold Level Support
#### Modern Chrome
- [ ] Mac OS + Chrome
- [ ] Windows OS + Chrome
- [ ] Mobile Galaxy Android OS + Chrome
- [ ] Mobile Pixel Android OS + Chrome

(Pick 3):
- [ ] iPhone 7 iOS + Chrome
- [ ] iPhone 8 iOS + Chrome
- [ ] iPhone 9 iOS + Chrome
- [ ] iPhone 10 iOS + Chrome
- [ ] iPhone 11 iOS + Chrome

#### Modern Safari
- [ ] Mac OS + Safari

(Pick 3):
- [ ] iPhone 7 iOS + Safari
- [ ] iPhone 8 iOS + Safari
- [ ] iPhone 9 iOS + Safari
- [ ] iPhone 10 iOS + Safari
- [ ] iPhone 11 iOS + Safari

#### Modern Firefox
- [ ] Mobile Galaxy Android OS + Firefox
- [ ] Mobile Pixel Android OS + Firefox


### Silver Level Support
#### Modern Firefox
- [ ] Mac OS + Firefox
- [ ] Windows OS + Firefox

#### Modern Chrome
- [ ] Tablet Android OS + Chrome

#### Modern Edge
- [ ] Windows OS + Edge

#### Internet Explorer
- [ ] Windows OS + IE 11

#### Modern Safari
- [ ] iPad iOS + Safari
