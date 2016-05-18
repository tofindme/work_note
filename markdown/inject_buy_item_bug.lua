
local target_service = 'snlua agent'
local host_service_addr = 0x1d -- gamed 服务

skynet = skynet or require 'skynet'

if skynet.self() == host_service_addr then

  local inject_file_name = 'inject_buy_item_bug.lua'
  local f = io.open(inject_file_name, 'rb')
  if not f then
    return print 'inject script file not found'
  end

  local source = f:read('a')
  f:close()


  print('即将更新全部agent')
  local c = 0
  for k, v in pairs(skynet.call('.launcher', 'lua', 'LIST')) do
    if v:match(target_service) then
      skynet.call(tonumber('0x' .. k:sub(2)), 'debug', 'RUN', source, 'i')
      c = c + 1
    end
  end
  print('全部agent更新完成! 一共更新了:[', c, ']个')

else

local cm = require 'common'
local msgid = cm.MESSAGE.CS_CMD_BUY_ITEM

local tf

for k, v in pairs(_P.client.cmd_handle_mapping) do
	if k == msgid then
		tf = v
		break
	end
end

if not tf then
	return
end

local debug = debug

local send_res, send_err, acc_info

local i = 1
while true do
	local name, value = debug.getupvalue(tf, i)
	if not name then
		break
	end
	if name == 'send_response' then
		send_res = i
	elseif name == 'send_error_code' then
		send_err = i
	elseif name == 'account_info' then
		acc_info = i
	end
	i = i + 1
end

local handle_buy_item_msg
do

  -- raw env cache
  local mti = getmetatable(_ENV).__index
  local require = mti.require
  local pairs = mti.pairs
  local math = mti.math
  local tonumber = mti.tonumber



  local skynet    = require "skynet"

  local setting   = require "setting"
  local COMMON    = require "common"
  local MESSAGE   = COMMON.MESSAGE
  local ERRORCODE = COMMON.ERRORCODE

  local logger    = require "logger"
  local proto     = require "proto"

  local sharedata_utils    = require "sharedata_utils"
  local agent_vip          = require "agent_vip"
  local agent_item         = require "agent_item"
  local player             = require "player"

  local buy_value = setting.game_setting.buy_value


  -- 需要手动处理的upvalue

  local send_response, send_error_code, account_info


  function handle_buy_item_msg(cmd, content, len)
      local ret = ERRORCODE.OK
      local shop_data = proto.parse_proto_req(cmd, content, len)
      local itemId = shop_data.ItemId
      local price = shop_data.Price
      local count = shop_data.Count
      local from = shop_data.From
      local src = 0

      local item_exist = false

      --联想网络版临时购买bug修复
      local buy_data = sharedata_utils.get_stat_all(COMMON.plan.ItemBuy)
      logger.debug('\n*******\n新购买函数;-)\n*******\n')
      for k,v in pairs(buy_data) do
          if itemId == v.name then
              price = v.moneynum
              count = v.num
              item_exist = true
              break
          end
      end

      if not item_exist and itemId ~= 1002 and itemId ~= 1001 then
          return send_error_code(cmd, ERRORCODE.ITEM_NOT_EXIST)
      end


      local discount = agent_vip.get_vip_privilege_param(6)
      if discount ~= 0 then
          price = math.floor(price*tonumber(discount)/10)
      end

      if from == 1 then
          src = COMMON.change_src.from_buy_item_pve
      elseif from == 2 then
          src = COMMON.change_src.from_buy_item_pvp
      elseif from == 0 then
          src = COMMON.change_src.from_buy_item
      else
          return logger.debugf("handle buy item msg error from[%d]", from)
      end

      if count > 0 then
          if account_info.diamond >= price then
              if itemId == 1002 then --1002体力
                  if player.add_power(account_info, count * buy_value) then
                    account_info.power_buy_count = account_info.power_buy_count + count
                    src = COMMON.change_src.from_buy_power
                  else
                    ret = ERRORCODE.POWER_IS_MAX
                  end
              elseif itemId == 1001 then --1001精力
                  if player.add_energy(account_info, count * buy_value) then
                    account_info.energy_buy_count = account_info.energy_buy_count + count
                    src = COMMON.change_src.from_buy_energy
                  else
                    ret = ERRORCODE.ENERGY_IS_MAX
                  end
              else
                  agent_item.add_item(itemId, count, src, true)
              end
          else
              ret = ERRORCODE.DIAMAON_NOT_ENOUGH_ERROR
          end
      else
          ret = ERRORCODE.ITEM_COUNT_ERROR
      end

      if ret == ERRORCODE.OK then
          player.change_diamond(account_info, src, -price)
          return send_response(MESSAGE.CS_CMD_BUY_ITEM, {
              Ret    = ret,
              ItemId = itemId,
              Count  = count,
              Price  = price,
          })
      else
          return send_error_code(cmd, ret)
      end
  end

end

assert(send_res, send_err, acc_info)

local i = 1
while true do
	local name, value = debug.getupvalue(handle_buy_item_msg, i)
	if not name then
		break
	end
	if name == 'send_response' then
		debug.upvaluejoin(handle_buy_item_msg, i, tf, send_res)
	elseif name == 'send_error_code' then
		debug.upvaluejoin(handle_buy_item_msg, i, tf, send_err)
	elseif name == 'account_info' then
		debug.upvaluejoin(handle_buy_item_msg, i, tf, acc_info)
	end

	i = i + 1
end


_P.client.cmd_handle_mapping[msgid] = handle_buy_item_msg

end
