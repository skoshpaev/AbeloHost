-- RENAME alone atomically detaches an immutable batch from the live buffer.
if redis.call('EXISTS', KEYS[2]) == 1 then
    return redis.error_reply('View batch identifier already exists')
end
if redis.call('EXISTS', KEYS[1]) == 0 then
    return 0
end
redis.call('RENAME', KEYS[1], KEYS[2])
return 1
