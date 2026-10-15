local active_type = redis.call('TYPE', KEYS[2]).ok
local totals_type = redis.call('TYPE', KEYS[3]).ok
if active_type ~= 'none' and active_type ~= 'hash' then
    return redis.error_reply('Active view buffer must be a hash')
end
if totals_type ~= 'none' and totals_type ~= 'hash' then
    return redis.error_reply('View totals must be a hash')
end

local total = redis.call('HGET', KEYS[3], ARGV[1])
if not total then
    redis.call('HSET', KEYS[3], ARGV[1], ARGV[3])
end
if redis.call('EXISTS', KEYS[1]) == 1 then
    return 0
end

redis.call('SET', KEYS[1], '1', 'EX', ARGV[2])
local active_count = redis.pcall('HINCRBY', KEYS[2], ARGV[1], 1)
if type(active_count) == 'table' and active_count.err then
    redis.call('DEL', KEYS[1])
    return redis.error_reply(active_count.err)
end
local total_count = redis.pcall('HINCRBY', KEYS[3], ARGV[1], 1)
if type(total_count) == 'table' and total_count.err then
    redis.call('HINCRBY', KEYS[2], ARGV[1], -1)
    if active_count == 1 then
        redis.call('HDEL', KEYS[2], ARGV[1])
    end
    redis.call('DEL', KEYS[1])
    return redis.error_reply(total_count.err)
end
return 1
