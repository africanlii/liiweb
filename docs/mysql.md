# MySQL configuration

1. Sometimes you need to increase the OS open files limit. Check out `ulimit` and other resources https://easyengine.io/tutorials/linux/increase-open-files-limit/

2. Sample config below assumes around 6GB allocated to MySQL, adjust to your environment, in particular `innodb_buffer_pool_size`.

```
[mysqld]
character-set-server=utf8
slow_query_log=1
slow_query_log_file="/var/log/mariadb/slow-query.log"

max_allowed_packet=1024M
net_read_timeout=7200
net_write_timeout=7200

open_files_limit=65535
max_connections=256

table_definition_cache=4096
table_open_cache=1024
thread_cache_size=128
key_buffer_size=64M
key_cache_block_size=4K
sort_buffer_size=3M
read_buffer_size=2M
read_rnd_buffer_size=64M
join_buffer_size=8M
tmp_table_size=256M
max_heap_table_size=256M
query_cache_size=128M
query_cache_limit=6M

innodb_file_per_table=1
innodb_thread_concurrency=0
innodb_strict_mode=1
innodb_log_file_size=512M
innodb_log_buffer_size=4M
innodb_buffer_pool_size=4G
innodb_lock_wait_timeout=3600
innodb_buffer_pool_instances=4
innodb_log_files_in_group=2
```
