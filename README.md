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

SQL query:
```
select b.* from author_book left join book b on b.id=book_id group by book_id having count(author_id) > 2;
```
