<?php
require_once 'vendor/autoload.php';

$pdo = new PDO('mysql:dbname=abcd-vid;localhost', 'root', '');
$seeder = new \tebazil\dbseeder\Seeder($pdo);
$generator = $seeder->getGeneratorConfigurator();

$faker = Faker\Factory::create();

// Generate participants
$participants = array();
$numberOfParticipants = 250;
$participantsColumns = ['id', 'firstName', 'lastName', 'dateOfBirth', 'createdOn'];
for($i = 0; $i < $numberOfParticipants; $i++) {
    $firstName = $faker->firstName;
    $lastName = $faker->lastName;

    $participants[] = [
        'id' => $i + 1,
        'firstName' => $firstName,
        'lastName' => $lastName,
        'dateOfBirth' => $faker->date('Y-m-d H:i:s', $max = 'now'),
        'createdOn' => date('Y-m-d H:i:s'),
    ];
}

$seeder->table('participants')->data($participants, $participantsColumns)->rowQuantity($numberOfParticipants);

$faker = Faker\Factory::create();

// Generate staff
$staff = array();
$numberOfStaff = 10;
$usersColumns = ['id', 'userName', 'firstName', 'lastName', 'password', 'eMail', 'createdDate', 'role'];
$staff[1] = [1, 'admin', 'Admin', 'User', md5('betterdatabase'), 'info@hellokrd.net', date("Y-m-d H:i:s"), 40];

// Generate other staff
for($i = 1; $i < $numberOfStaff; $i++) {
    $firstName = $faker->firstName;
    $lastName = $faker->lastName;
    $username = strtolower($firstName . '.' . $lastName);

    $staff[$i + 1] = [
        'id' => $i + 1,
        'userName' => strtolower($username),
        'firstName' => $firstName,
        'lastName' => $lastName,
        'password' => md5('betterdatabase'),
        'eMail' => $username . '@betterdatabase.ca',
        'createdDate' => date("Y-m-d H:i:s"),
        'role' => 20
    ];
}

// Generate volunteers
$volunteers = array();
$numberOfVolunteers = 100;
$volunteerColumns = ['id', 'userName', 'firstName', 'lastName', 'password', 'eMail', 'createdDate', 'role'];

for($i = 0; $i < $numberOfVolunteers; $i++) {
    $firstName = $faker->firstName;
    $lastName = $faker->lastName;
    $username = strtolower($firstName . '.' . $lastName);

    $volunteers[$numberOfStaff + $i + 1] = [
        'id' => $numberOfStaff + $i + 1,
        'userName' => strtolower($username),
        'firstName' => $firstName,
        'lastName' => $lastName,
        'password' => md5('betterdatabase'),
        'eMail' => $username . '@betterdatabase.ca',
        'createdDate' => date("Y-m-d H:i:s"),
        'role' => 15
    ];
}

$users = array_merge($staff, $volunteers);

var_dump($users);

$seeder->table('users')->data($users, $usersColumns)->rowQuantity($numberOfStaff + $numberOfVolunteers);

// Generate departments
$departments = [
    1 => [
        'id' => 1,
        'deptName' => 'Counselling'
    ],
    2 => [
        'id' => 2,
        'deptName' => 'Community Development'
    ],
    3 => [
        'id' => 3,
        'deptName' => 'Outreach'
    ],
];
$departmentColumns = ['id', 'deptName'];
$seeder->table('departments')->data($departments, $departmentColumns)->rowQuantity(count($departments));

// Generate Programs
$programs = [
    1 => [
        'id' => 1, 'deptID' => $departments[1]['id'], 
        'name' => 'Direct Counselling', 'type' => 'oneToOne'],
    2 => [
        'id' => 2, 'deptID' => $departments[1]['id'], 
        'name' => 'Parenting Skills', 'type' => 'group'],
    3 => [
        'id' => 3, 'deptID' => $departments[1]['id'], 
        'name' => 'Life Skills', 'type' => 'group'], 
    4 => [
        'id' => 4, 'deptID' => $departments[2]['id'], 
        'name' => 'Youth and Families', 'type' => 'group'],
    5 => [
        'id' => 5, 'deptID' => $departments[2]['id'], 
        'name' => 'Healthy Neighbourhoods', 'type' => 'group'],
    6 => [
        'id' => 6, 'deptID' => $departments[2]['id'], 
        'name' => 'Aging Well', 'type' => 'group'],
    7 => [
        'id' => 7, 'deptID' => $departments[3]['id'], 
        'name' => 'In-home Supports', 'type' => 'oneToOne'],
    8 => [
        'id' => 8, 'deptID' => $departments[3]['id'], 
        'name' => 'Food Hampers', 'type' => 'oneToOne'],
];
$programColumns = ['id', 'deptID', 'name', 'volunteerType'];
$seeder->table('programs')->data($programs, $programColumns)->rowQuantity(count($programs));

// Generate Groups
$groups = [
    [
        'id' => 1, 
        'programID' => $programs[1]['id'], 'Moms and Tots', '', date("Y-m-d H:i:s")],
    [
        'id' => 2, 
        'programID' => $programs[1]['id'], 'New Fathers', 'Support and teaching group for new fathers', date("Y-m-d H:i:s")],
    [
        'id' => 3, 
        'programID' => $programs[1]['id'], 'Finance For Families', 'Providing financial workshops and support for families', date("Y-m-d H:i:s")],
    [
        'id' => 4, 
        'programID' => $programs[2]['id'], 'Anger Management', '', date("Y-m-d H:i:s")],
    [
        'id' => 5, 
        'programID' => $programs[2]['id'], 'Financial Literacy', 'Increasing financial literacy for all individuals', date("Y-m-d H:i:s")],
    [
        'id' => 6, 
        'programID' => $programs[2]['id'], 'Art Therapy', '', date("Y-m-d H:i:s")],
    [
        'id' => 7, 
        'programID' => $programs[3]['id'], 'After-school Spring 2019', '', '2019-04-01'],
    [
        'id' => 8, 
        'programID' => $programs[3]['id'], 'After-school Fall 2019', '', '2019-09-01'],
    [
        'id' => 9, 
        'programID' => $programs[3]['id'], 'After-school Spring 2020', '', '2020-04-01'],
    [
        'id' => 10, 
        'programID' => $programs[3]['id'], 'After-school Fall 2020', '', '2020-09-01'],
    [
        'id' => 11, 
        'programID' => $programs[3]['id'], 'After-school Spring 2021', '', '2021-04-01'],
    [
        'id' => 12, 
        'programID' => $programs[4]['id'], 'Meet our Neighbours', '', date("Y-m-d H:i:s")],
    [
        'id' => 13, 
        'programID' => $programs[4]['id'], 'Philosophy Walks', '', date("Y-m-d H:i:s")],
    [
        'id' => 14, 
        'programID' => $programs[4]['id'], 'Birds of a Feather', '', date("Y-m-d H:i:s")],
    [
        'id' => 15, 
        'programID' => $programs[5]['id'], 'Conscious Eldering', '', date("Y-m-d H:i:s")],
    [
        'id' => 16, 
        'programID' => $programs[5]['id'], 'Death Cafe', '', date("Y-m-d H:i:s")],
    [
        'id' => 17, 
        'programID' => $programs[5]['id'], 'Elder Service Corps', '', date("Y-m-d H:i:s")],
];
$groupColumns = ['id', 'programID', 'name', 'description', 'beginDate'];
$seeder->table('groups')->data($groups, $groupColumns)->rowQuantity(count($groups));

/**
 * Associate participants with groups
 * Since some programs have no groups, those programs and their departments
 * don't have participants.
 */
$group_participant = array();
$program_participant = array();
$department_participant = array();

$group_participant_columns = ['participantID', 'groupID', 'enrollDate'];
$program_participant_columns = ['participantID', 'programID', 'enrollDate', 'status', 'statusDate'];
$department_participant_columns = ['participantID', 'deptID'];
foreach($participants as $participant) {
    $groupIndex = array_rand($groups, 1);
    $program = $groups[$groupIndex]['programID'];
    $department = $programs[$program]['deptID'];

    $department_participant[] = [$participant['id'], $department];
    $program_participant[] = [$participant['id'], $program, date("Y-m-d"), 'waitlist', date("Y-m-d H:i:s")];
    $group_participant[] = [$participant['id'], $groups[$groupIndex]['id'], date("Y-m-d"), $faker->dateTimeBetween('now', '+2 months')];
}
// Set participants for the groups.
$seeder->table('participantGroups')->data($group_participant, $group_participant_columns)->rowQuantity(count($group_participant));

/**
 * Associate participants with programs with no groups
 */
$program_ids = array_keys($programs);
$program_ids_with_groups = array_unique(array_column($groups, 'programID'));
$program_ids_with_no_groups = array_diff($program_ids, $program_ids_with_groups); 

$program_participant_columns = ['participantID', 'programID', 'enrollDate', 'status', 'statusDate'];
$department_participant_columns = ['participantID', 'deptID'];
foreach($participants as $participant) {
    $index = array_rand($program_ids_with_no_groups, 1);
    $program = $program_ids_with_no_groups[$index];
    $department = $programs[$program]['deptID'];

    $department_participant[] = [$participant['id'], $department];
    $program_participant[] = [$participant['id'], $program, date("Y-m-d"), 'waitlist', date("Y-m-d H:i:s")];
}

// Add participants to programs (with and without groups) and respective departments.
$seeder->table('participantDepts')->data($department_participant, $department_participant_columns)->rowQuantity(count($department_participant));
$seeder->table('participantPrograms')->data($program_participant, $program_participant_columns)->rowQuantity(count($program_participant));

/**
 * Associate volunteers with groups
 */
$group_volunteer = array();
$program_user = array();
$department_user = array();

$group_volunteer_columns = ['volunteerID', 'groupID', 'enrollDate'];
$program_user_columns = ['userID', 'programID'];
$department_user_columns = ['userID', 'deptID'];
foreach($volunteers as $volunteer) {
    $groupIndex = array_rand($groups, 1);

    $group_volunteer[] = [$volunteer['id'], $groups[$groupIndex]['id'], date("Y-m-d"), $faker->dateTimeBetween('now', '+2 months')];
    $program_user[] =   [$volunteer['id'], $groups[$groupIndex]['programID']];
    $department_user[] = [$volunteer['id'], $programs[$groups[$groupIndex]['programID']]['deptID']];
}

// Set volunteers for the groups.
$seeder->table('volunteerGroups')->data($group_volunteer, $group_volunteer_columns)->rowQuantity(count($group_volunteer));
$seeder->table('userPrograms')->data($program_user, $program_user_columns)->rowQuantity(count($program_user));
$seeder->table('userDepartments')->data($department_user, $department_user_columns)->rowQuantity(count($department_user));

// Seed!
$seeder->refill();