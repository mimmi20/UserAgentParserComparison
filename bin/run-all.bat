cd ..
php -d memory_limit=1024M bin\cache\initBrowscap.php
php bin\cache\initMatomo.php
php bin\cache\initWhichBrowser.php
php bin\cache\initBrowserDetector.php
php bin\db\initDb.php
php bin\db\initProviders.php
php bin\db\initUserAgents.php
php bin\db\initResults.php

php bin\html\index.php
php bin\html\overview-general.php
php bin\html\overview-provider.php
