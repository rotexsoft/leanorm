#!/usr/bin/env php
<?php
function readFromLine( $prompt = '' ) {
        
    echo $prompt;
    return trim(rtrim( fgets( STDIN ), PHP_EOL ));
}

function sleepWithEcho(int $seconds) {
    
    echo 'Sleeping....' . PHP_EOL;
    sleep($seconds);
    echo 'Waking....' . PHP_EOL;
}

function echoWithLineBreaks(string $str): void {
    
    echo PHP_EOL . $str . PHP_EOL;
}

function readableElapsedTime($microtime, $format = null, $round = 3) {
    
    if (is_null($format)) {
        $format = '%.3f%s';
    }

    if ($microtime >= 3600) {
        
        $unit = ' hour(s)';
        $time = round(($microtime / 3600), $round);
        
    } elseif ($microtime >= 60) {
        
        $unit = ' minute(s)';
        $time = round(($microtime / 60), $round);
        
    } elseif ($microtime >= 1) {
        
        $unit = ' second(s)';
        $time = round($microtime, $round);
        
    } else {
        
        $unit = 'ms';
        $time = round($microtime*1000);

        $format = preg_replace('/(%.[\d]+f)/', '%d', $format);
    }

    return sprintf($format, $time, $unit);
}

$new_line = PHP_EOL;
echoWithLineBreaks(
    'WARNING: This test suite could take up to 4 hours to run depending on the'
    . ' performance capability of this computer.' . $new_line
    . "\t Podman also needs to be installed in order for the tests to run." . $new_line
);

$console_prompt = "If you have an instance of (MySql / Mariadb) and / or Postgresql"
                . " running, please stop them and then press Enter.{$new_line}This"
                . " script will be spawning new container instances of MySql & Postgresql"
                . " that will be forwarding to ports 3306 and 5432 on this machine:";
$console_response = readFromLine($console_prompt); // hitting enter returns an empty string, 
                                                   // maybe later other options could be read
                                                   // into this variable     

$console_prompt2 = "Please enter a password that would be used for the Mysql & Mariadb root accounts:";
$mysql_root_psw = readFromLine(PHP_EOL . $console_prompt2);

$test_results =  [];

$mysql_maria_db_sql_dsn = 'mysql:host=127.0.0.1;port=3306';
$mysql_user = 'root';

$pgsql_dsn = 'pgsql:host=127.0.0.1;port=5432;user=postgres;password=doesntmatter;dbname=blog';
$pgsql_user = 'postgres';
$pgsql_pass = 'doesntmatter';

////////////////////////////////////////////////////////////////////////////////
// This array should be updated periodically with new db image versions added
// and old versions that are no longer supported removed.
////////////////////////////////////////////////////////////////////////////////
$container_creation_commands = [
     [
         'mysql:5.6.51' => [
             'run_container' => "podman run -dt -p 3306:3306 -e MYSQL_ROOT_PASSWORD={$mysql_root_psw} docker.io/library/mysql:5.6.51",
             'dsn' => $mysql_maria_db_sql_dsn,
             'username' => $mysql_user,
             'password' => $mysql_root_psw,
         ],
     ],
     [
         'postgres:12.18' => [
             'run_container' => "podman run -dt -p 5432:5432 -e POSTGRES_HOST_AUTH_METHOD=trust -e POSTGRES_DB=blog docker.io/library/postgres:12.18",
             'dsn' => $pgsql_dsn,
             'username' => $pgsql_user,
             'password' => $pgsql_pass,
         ], 
     ],
     [
         'mysql:5.7.44' => [
             'run_container' => "podman run -dt -p 3306:3306 -e MYSQL_ROOT_PASSWORD={$mysql_root_psw} docker.io/library/mysql:5.7.44",
             'dsn' => $mysql_maria_db_sql_dsn,
             'username' => $mysql_user,
             'password' => $mysql_root_psw,
         ],
     ],
     [
         'postgres:13.14' => [
             'run_container' => "podman run -dt -p 5432:5432 -e POSTGRES_HOST_AUTH_METHOD=trust -e POSTGRES_DB=blog docker.io/library/postgres:13.14",
             'dsn' => $pgsql_dsn,
             'username' => $pgsql_user,
             'password' => $pgsql_pass,
         ], 
     ],
     [
         'mysql:8.0.36' => [
             'run_container' => "podman run -dt -p 3306:3306 -e MYSQL_ROOT_PASSWORD={$mysql_root_psw} docker.io/library/mysql:8.0.36",
             'dsn' => $mysql_maria_db_sql_dsn,
             'username' => $mysql_user,
             'password' => $mysql_root_psw,
         ],
     ],
     [
         'postgres:14.11' => [
             'run_container' => "podman run -dt -p 5432:5432 -e POSTGRES_HOST_AUTH_METHOD=trust -e POSTGRES_DB=blog docker.io/library/postgres:14.11",
             'dsn' => $pgsql_dsn,
             'username' => $pgsql_user,
             'password' => $pgsql_pass,
         ], 
     ],
    [
        'mysql:8.1.0' => [
            'run_container' => "podman run -dt -p 3306:3306 -e MYSQL_ROOT_PASSWORD={$mysql_root_psw} docker.io/library/mysql:8.1.0",
            'dsn' => $mysql_maria_db_sql_dsn,
            'username' => $mysql_user,
            'password' => $mysql_root_psw,
        ],
    ],
    [
        'mysql:8.2.0' => [
            'run_container' => "podman run -dt -p 3306:3306 -e MYSQL_ROOT_PASSWORD={$mysql_root_psw} docker.io/library/mysql:8.2.0",
            'dsn' => $mysql_maria_db_sql_dsn,
            'username' => $mysql_user,
            'password' => $mysql_root_psw,
        ],
    ],
    [
        'mysql:8.3.0' => [
            'run_container' => "podman run -dt -p 3306:3306 -e MYSQL_ROOT_PASSWORD={$mysql_root_psw} docker.io/library/mysql:8.3.0",
            'dsn' => $mysql_maria_db_sql_dsn,
            'username' => $mysql_user,
            'password' => $mysql_root_psw,
        ],
    ],
    [
        'mysql:8.4.0' => [
            'run_container' => "podman run -dt -p 3306:3306 -e MYSQL_ROOT_PASSWORD={$mysql_root_psw} docker.io/library/mysql:8.4.0",
            'dsn' => $mysql_maria_db_sql_dsn,
            'username' => $mysql_user,
            'password' => $mysql_root_psw,
        ],
    ],
     [
         'postgres:15.6' => [
             'run_container' => "podman run -dt -p 5432:5432 -e POSTGRES_HOST_AUTH_METHOD=trust -e POSTGRES_DB=blog docker.io/library/postgres:15.6",
             'dsn' => $pgsql_dsn,
             'username' => $pgsql_user,
             'password' => $pgsql_pass,
         ], 
     ],
     [
         'postgres:16.2' => [
             'run_container' => "podman run -dt -p 5432:5432 -e POSTGRES_HOST_AUTH_METHOD=trust -e POSTGRES_DB=blog docker.io/library/postgres:16.2",
             'dsn' => $pgsql_dsn,
             'username' => $pgsql_user,
             'password' => $pgsql_pass,
         ], 
     ],
     [
         'mariadb:10.4.33' => [
             'run_container' => "podman run -dt -p 3306:3306 -e MYSQL_ROOT_PASSWORD={$mysql_root_psw} docker.io/library/mariadb:10.4.33",
             'dsn' => $mysql_maria_db_sql_dsn,
             'username' => $mysql_user,
             'password' => $mysql_root_psw,
         ],
     ],
     [
         'mariadb:10.5.24' => [
             'run_container' => "podman run -dt -p 3306:3306 -e MYSQL_ROOT_PASSWORD={$mysql_root_psw} docker.io/library/mariadb:10.5.24",
             'dsn' => $mysql_maria_db_sql_dsn,
             'username' => $mysql_user,
             'password' => $mysql_root_psw,
         ],
     ],
     [
         'mariadb:10.6.17' => [
             'run_container' => "podman run -dt -p 3306:3306 -e MYSQL_ROOT_PASSWORD={$mysql_root_psw} docker.io/library/mariadb:10.6.17",
             'dsn' => $mysql_maria_db_sql_dsn,
             'username' => $mysql_user,
             'password' => $mysql_root_psw,
         ],
     ],
     [
         'mariadb:10.11.7' => [
             'run_container' => "podman run -dt -p 3306:3306 -e MYSQL_ROOT_PASSWORD={$mysql_root_psw} docker.io/library/mariadb:10.11.7",
             'dsn' => $mysql_maria_db_sql_dsn,
             'username' => $mysql_user,
             'password' => $mysql_root_psw,
         ],
     ],
     [
         'mariadb:11.0.5' => [
             'run_container' => "podman run -dt -p 3306:3306 -e MYSQL_ROOT_PASSWORD={$mysql_root_psw} docker.io/library/mariadb:11.0.5",
             'dsn' => $mysql_maria_db_sql_dsn,
             'username' => $mysql_user,
             'password' => $mysql_root_psw,
         ],
     ],
     [
         'mariadb:11.1.4' => [
             'run_container' => "podman run -dt -p 3306:3306 -e MYSQL_ROOT_PASSWORD={$mysql_root_psw} docker.io/library/mariadb:11.1.4",
             'dsn' => $mysql_maria_db_sql_dsn,
             'username' => $mysql_user,
             'password' => $mysql_root_psw,
         ], 
     ],
     [
         'mariadb:11.2.3' => [
             'run_container' => "podman run -dt -p 3306:3306 -e MYSQL_ROOT_PASSWORD={$mysql_root_psw} docker.io/library/mariadb:11.2.3",
             'dsn' => $mysql_maria_db_sql_dsn,
             'username' => $mysql_user,
             'password' => $mysql_root_psw,
         ],
     ],
     [
         'mariadb:11.3.2' => [
             'run_container' => "podman run -dt -p 3306:3306 -e MYSQL_ROOT_PASSWORD={$mysql_root_psw} docker.io/library/mariadb:11.3.2",
             'dsn' => $mysql_maria_db_sql_dsn,
             'username' => $mysql_user,
             'password' => $mysql_root_psw,
         ],
     ],
];
      
echo PHP_EOL . PHP_EOL . 'Starting to create containers and run tests....' . PHP_EOL . PHP_EOL;

$phpunit = __DIR__ . '/vendor/bin/phpunit --coverage-text --stop-on-error';
$start_time = microtime(true);

foreach($container_creation_commands as $current_container_creation_command) {
    
    $db_versions = '';
    
    foreach($current_container_creation_command as $db_version => $command ) {
        
        $db_versions .= $db_version . PHP_EOL;
        
        $retval=null;
        $output=null;
        echoWithLineBreaks($command['run_container']);
        exec($command['run_container'], $output, $retval);
        
        echo PHP_EOL . PHP_EOL;
    }
    
    sleepWithEcho(90); // allow databases to load properly

    echo PHP_EOL . PHP_EOL;
    
    $output = null;
    $phpunit_retval = null;
    
    // Print out current db versions being tested with
    echo "Testing against the following databases:" .PHP_EOL . $db_versions . PHP_EOL;
    
    $phpunit_with_env = 
        "LEANORM_PDO_DSN=\"{$command['dsn']}\" LEANORM_PDO_USERNAME={$command['username']} LEANORM_PDO_PASSWORD={$command['password']} {$phpunit}";
    
    echoWithLineBreaks($phpunit_with_env);
    system($phpunit_with_env, $phpunit_retval);

    echo "Stopping container instances & deleting their images" .PHP_EOL . PHP_EOL;
    
    echoWithLineBreaks("podman stop -a");
    system("podman stop -a"); // stop the containers
    
    // remove their images from the machine to save disk space
    echoWithLineBreaks("podman system prune --all --force");
    system("podman system prune --all --force");
    
    echoWithLineBreaks("podman rmi --all");
    system("podman rmi --all");
    
    echoWithLineBreaks("podman volume rm --all --force");
    system("podman volume rm --all --force");
    
    if($phpunit_retval !== 0) {
        
        ////////////////////////////////////////////////////////////////////////
        // A PHPUnit Test failed.
        // if $phpunit_retval !== 0, test failed, stop containers and exit
        // see https://github.com/sebastianbergmann/phpunit/blob/10.5/src/TextUI/ShellExitCodeCalculator.php
        // for phpunit shell exit codes
        ////////////////////////////////////////////////////////////////////////
        echo PHP_EOL . "Test failed for the following databases:" . PHP_EOL . $db_versions;
        echo PHP_EOL . 'Goodbye!'. PHP_EOL;
        exit(1);
        
    } eLse {
        
        $test_results[] = $db_versions;
        echo PHP_EOL;
        
    } // if($phpunit_retval !== 0)
} // foreach($postgres_and_mysql_container_creation_commands as $postgres_and_mysql_container_creation_command)

$end_time = microtime(true);

$elapsed = $end_time - $start_time;

if (count($test_results) > 0) {
    
    echo PHP_EOL . PHP_EOL 
        . "Test passed for the following databases:" 
        . PHP_EOL . PHP_EOL;
    
    foreach ($test_results as $test_result) {

        echo $test_result . PHP_EOL;
        
    } // foreach ($test_results as $test_result)
} // if (count($test_results) > 0)

echo PHP_EOL . 'Time taken: ' . readableElapsedTime($elapsed). PHP_EOL. PHP_EOL;
echo PHP_EOL . 'Goodbye!'. PHP_EOL;
