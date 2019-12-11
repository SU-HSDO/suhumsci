# Sparkbox Pull Request Checklist

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
- [ ] Are markup and styles accessible?

#### Security
- [ ] Do forms need to be sanitized?
- [ ] Any obvious security flaws or potential holes?

#### Localization/Internationalization
- [ ] Is all UI content (labels and other strings, but _NOT_ user generated content) translated?
  - Make use of the `trans` or `t` filter


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