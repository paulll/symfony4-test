# Test
Learning symfony v4

To run:
```
git clone git@github.com:paulll/symfony4-test.git && cd symfony4-test \
&& composer install \
&& php bin/console doctrine:schema:update --force \
&& bin/console fos:user:create --super-admin admin 'admin@localhost' admin \
&& symfony server:start 
```
