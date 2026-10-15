local totals_type = redis.call('TYPE', KEYS[1]).ok
if totals_type ~= 'none' and totals_type ~= 'hash' then
    return redis.error_reply('View totals must be a hash')
end

local article_ids = {}
for index = 1, #ARGV, 2 do
    local article_id = ARGV[index]
    local initial_total = ARGV[index + 1]
    if not string.match(article_id, '^%d+$') or not string.match(initial_total, '^%d+$') then
        return redis.error_reply('Invalid view total input')
    end
    if redis.call('HEXISTS', KEYS[1], article_id) == 0 then
        redis.call('HSET', KEYS[1], article_id, initial_total)
    end
    table.insert(article_ids, article_id)
end

if #article_ids == 0 then
    return {}
end
return redis.call('HMGET', KEYS[1], unpack(article_ids))
