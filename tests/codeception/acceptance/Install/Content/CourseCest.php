<?php

class CourseCest {

  public function testCourses(AcceptanceTester $I) {
    $I->logInWithRole('contributor');
    $I->amOnPage('/node/add/hs_course');

    $I->fillField('Title', 'Title');
    $I->fillField('Requirements', 'Requirements');
    $I->fillField('Course Code', 'Course Code');
    $I->fillField('Course Code Integer', '111');
    $I->fillField('Course ID', '222');
    $I->fillField('Grading', 'Grading');
    $I->fillField('Component', 'Component');
    $I->fillField('Subject', 'Subject');
    $I->fillField('Units', 'Units');
    $I->fillField('Course Tags (value 1)', 'Course Tags');
    $I->fillField('Course Link', 'http://google.com');
    $I->fillField('Body', 'Body');
    $I->fillField('Section ID', '333');
    $I->fillField('Section Number', '444');
    $I->fillField('Location', 'Location');
    $I->fillField('Section Days', 'Section Days');
    $I->fillField('field_hs_course_section_st_date[0][value][date]', '2028-10-01');
    $I->fillField('Start Time', '10:00 AM');
    $I->fillField('field_hs_course_section_end_date[0][value][date]', '2028-12-31');
    $I->fillField('End Time', '11:00 AM');

    $I->selectOption('Academic Year', '2028 - 2029');
    $I->selectOption('Quarter', 'Autumn');
    $I->selectOption('Academic Career', 'Undergraduate');
    $I->click('Save');
    $I->canSeeInCurrentUrl('/courses/title/444');
    $I->canSeeResponseCodeIs(200);
    $I->canSee('Title', 'h1');
    $I->canSee('Requirements');
  }

}
