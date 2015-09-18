Moodle Behat 2 JMX
==========================
This is part of the Moodle performance toolkit, used for converting behat features into jMeter testplan (jmx).
Default feature expects the site has test data generated from [moodle-behat-generator](https://github.com/rajeshtaneja/moodle-behat-generator.git)
* warmup - it is a warmup threadgroup.
* student_view_activity - Student view all activities in moodle
* forum_post: Student view forum post and add discussion.

## Requirements:
* MySQL or PostgresSQL or Mariadb or MSSQL or oracle
* git
* curl
* PHP 5.4.4+
* Java 6 or later
* Browsermob proxy: http://bmp.lightbody.net/
* Selenium

## Generate test plan with following sizes:
Tool supports 5 sizes, according to specified size appropriate testplan will be generated.
* [xs] - Extra small
* [s] - Small
* [m] - Medium
* [l] - Large
* [xl] - Extra large

# Create JMeter test plan:
## Start BrowserMObProxy
Download latest version of the BrowserMob Proxy & Start it. This is used for capturing http requests and allowing user to select which http request user wants to include in test plan, with which params.
```sh
cd browsermob-proxy-xx/bin
./browsermob-proxy -port 9090
```

## Start selenium server
```sh
java -jar PATH_TOSELENIUM/selenium_server.jar
```

## Enable testplan
```
cd bin; ./moodle_behat_2jmx --testdata S -proxyurl "localhost:9090" --moodlepath {PATH_TO_MOODLE_SOURCE} --datapath {PATH_OF_DIR_TO_STORE_TEST_DATA_FILES}
```
> If you are creating testplan with default features then use --force option.
For custom features, input is required, so you need to execute the command shown by output of previous command.


# Background
Testplan is composed of series of http requests. Jmeter request server with these requests and http response samples are collected.
These samples are parsed to extract performance. In addition to this jMeter tracks time taken to get response.

