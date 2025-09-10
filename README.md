
# UserAgentParserComparison

We took thousands of user agent string and run them against different parsers...

[Here are the results](http://mimmi20.github.io/UserAgentParserComparison/)

## Installation

### Step1) Download this repo

Download this repo to a folder

### Step2) Install dependencies

```shell
composer update -o --prefer-source
```

### Step 3) Download files

#### Browscap

Download all (currently 3) `browscap.ini` files for PHP from [browscap.org](http://browscap.org/)

And put it to `data/*.ini`

### Step 4) init caches

```shell
php -d memory_limit=1024M bin/cache/initBrowscap.php
php bin/cache/initMatomo.php
php bin/cache/initBrowserDetector.php
php bin/cache/initWhichBrowser.php
```

### Step 5) config

Copy the `config.php.dist` to `config.php`
Copy the `bin/getChainProvider.php.dist` to `bin/getChainProvider.php`

And adjust your configuration

### Step 6) Init database

```shell
php bin/db/initDb.php
php bin/db/initProviders.php
php bin/db/initUserAgents.php
php -d browscap=data/full_php_browscap.ini bin/db/initResults.php
```

#### For vNEXT (not needed until yet)

```shell
php bin/db/initResultsEvaluation.php
php bin/db/initUserAgentsEvaluation.php
```

## Step 7) Generate reports

```shell
php bin/html/*.php # just all inside that folder
```

## Step 8) Run your own queries

After executing Step 5) you have already all data you need inside your `mysql` database!

So do whatever you want ;-)
