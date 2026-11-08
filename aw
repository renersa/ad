-- RenVersal Loader: detects your executor, recommends Original or Lite, runs it, and waits until it actually appears.
local URLS = {
	Original = "https://raw.githubusercontent.com/renersa/mainloader/refs/heads/main/RenVersal",
	Lite = "https://pastebin.com/raw/vBtuxAqh",
}
local REAL_URL = "https://projectreal.gg"
local LOGO = "rbxassetid://98205442743287"
-- the logo image holds the mark (R + V) on the left and the "RenVersal" text on the right; we only want the mark
local MARK_CX, MARK_W = 0.195, 2.6 -- mark centre (fraction of image width) and zoom; tweak if the crop looks off

local Players = game:GetService("Players")
local TS = game:GetService("TweenService")
local RS = game:GetService("RunService")
local UIS = game:GetService("UserInputService")
local genv = (getgenv and getgenv()) or _G
pcall(function() if genv.__rvLoaderGui then genv.__rvLoaderGui:Destroy() end end)

-- ===== executor detection =====
local function detect()
	local nm = "Unknown"
	pcall(function()
		if identifyexecutor then
			local a, b = identifyexecutor()
			nm = tostring(a or "Unknown")
			if b and tostring(b) ~= "" then nm = nm .. " " .. tostring(b) end
		elseif getexecutorname then nm = tostring(getexecutorname()) end
	end)
	local low = nm:lower()
	local weak = false
	for _, w in ipairs({ "xeno", "solara", "jjsploit", "temple", "krnl", "fluxus", "electron", "oxygen", "evon", "unknown" }) do
		if low:find(w, 1, true) then weak = true break end
	end
	local caps = { { "hookmetamethod", hookmetamethod }, { "request", request or http_request or (syn and syn.request) }, { "writefile", writefile }, { "getcustomasset", getcustomasset or getsynasset }, { "Drawing", Drawing }, { "getconnections", getconnections }, { "firetouchinterest", firetouchinterest } }
	local miss = {}
	for _, c in ipairs(caps) do if c[2] == nil then miss[#miss + 1] = c[1] end end
	local mobile = UIS.TouchEnabled and not UIS.KeyboardEnabled
	return { name = nm, weak = weak or #miss >= 3, miss = miss, mobile = mobile }
end
local EX = detect()
local rec, why
if EX.weak then rec, why = "Lite", "Your executor is limited, so the full script may not run. Lite is the safe choice."
elseif EX.mobile then rec, why = "Lite", "Phone detected. Lite is the version verified on phones; Original may not run."
else rec, why = "Original", "Your executor supports everything. Original has all the features." end

-- ===== gui helpers =====
local parent
pcall(function() parent = gethui() end)
if typeof(parent) ~= "Instance" then pcall(function() parent = game:GetService("CoreGui") end) end
if typeof(parent) ~= "Instance" then parent = Players.LocalPlayer:WaitForChild("PlayerGui") end
local function mk(c, p, par)
	local o = Instance.new(c)
	for k, v in pairs(p or {}) do o[k] = v end
	if par then o.Parent = par end
	return o
end
local function corner(o, r) mk("UICorner", { CornerRadius = UDim.new(0, r) }, o) end
local C = { bg = Color3.fromRGB(11, 13, 19), card = Color3.fromRGB(19, 22, 31), panel = Color3.fromRGB(26, 30, 42), line = Color3.fromRGB(48, 54, 72), txt = Color3.fromRGB(238, 241, 248), dim = Color3.fromRGB(144, 152, 172),
	red = Color3.fromRGB(226, 42, 54), deep = Color3.fromRGB(140, 18, 36), white = Color3.fromRGB(255, 255, 255), amber = Color3.fromRGB(255, 190, 84), ok = Color3.fromRGB(84, 208, 140), bad = Color3.fromRGB(240, 90, 90) }
local function lbl(par, t, sz, col, pos, size, font, al)
	return mk("TextLabel", { BackgroundTransparency = 1, Text = t, TextSize = sz, TextColor3 = col or C.txt, Position = pos or UDim2.new(), Size = size or UDim2.new(1, 0, 0, sz + 4), Font = font or Enum.Font.GothamMedium, TextXAlignment = al or Enum.TextXAlignment.Left, TextWrapped = true, TextYAlignment = Enum.TextYAlignment.Top }, par)
end
local function grad(o, a, b, rot) return mk("UIGradient", { Color = ColorSequence.new(a, b), Rotation = rot or 0 }, o) end
-- logo mark only (no "RenVersal" text): oversize the image inside a clipping frame so only the mark shows
local function markImg(par, S, pos, z, anchor)
	local f = mk("Frame", { AnchorPoint = anchor, Size = UDim2.fromOffset(S, S), Position = pos, BackgroundTransparency = 1, ClipsDescendants = true, ZIndex = z }, par)
	local W = S * MARK_W
	local im = mk("ImageLabel", { BackgroundTransparency = 1, Image = LOGO, ScaleType = Enum.ScaleType.Fit, Size = UDim2.fromOffset(W, W), Position = UDim2.fromOffset(S / 2 - MARK_CX * W, S / 2 - W / 2), ZIndex = z }, f)
	return f, im
end
-- shiny "Ren" (white) + "Versal" (red) wordmark
local shiny = {}
local function wordmark(par, pos, size, z)
	local row = mk("Frame", { BackgroundTransparency = 1, Position = pos, Size = UDim2.fromOffset(200, size + 6), ZIndex = z }, par)
	mk("UIListLayout", { FillDirection = Enum.FillDirection.Horizontal, VerticalAlignment = Enum.VerticalAlignment.Center }, row)
	local function part(txt, a, b)
		local t = mk("TextLabel", { BackgroundTransparency = 1, Text = txt, Font = Enum.Font.GothamBlack, TextSize = size, TextColor3 = C.white, AutomaticSize = Enum.AutomaticSize.X, Size = UDim2.fromOffset(0, size + 6), ZIndex = z }, row)
		local g = mk("UIGradient", { Color = ColorSequence.new({ ColorSequenceKeypoint.new(0, a), ColorSequenceKeypoint.new(0.5, b), ColorSequenceKeypoint.new(1, a) }) }, t)
		shiny[#shiny + 1] = g
		mk("UIStroke", { Color = Color3.fromRGB(0, 0, 0), Thickness = 1, Transparency = 0.6 }, t)
	end
	part("Ren", Color3.fromRGB(196, 202, 216), Color3.fromRGB(255, 255, 255))
	part("Versal", Color3.fromRGB(176, 22, 36), Color3.fromRGB(255, 96, 104))
	return row
end

local sg = mk("ScreenGui", { Name = "RVLoader", ResetOnSpawn = false, IgnoreGuiInset = true, DisplayOrder = 2147483000, ZIndexBehavior = Enum.ZIndexBehavior.Sibling }, parent)
genv.__rvLoaderGui = sg
local vpS = (workspace.CurrentCamera and workspace.CurrentCamera.ViewportSize) or Vector2.new(800, 400)

-- ===== loading screen (logo mark + wavy text + percent) =====
local LS = mk("Frame", { Size = UDim2.fromScale(1, 1), BackgroundColor3 = Color3.fromRGB(8, 9, 14), BackgroundTransparency = 0.15, BorderSizePixel = 0, ZIndex = 50, Active = true }, sg)
local big = math.clamp(math.floor(vpS.Y * 0.32), 90, 220)
local lmark = markImg(LS, big, UDim2.new(0.5, 0, 0.5, -20), 51, Vector2.new(0.5, 0.5))
local lscale = mk("UIScale", { Scale = 0.2 }, lmark)
TS:Create(lscale, TweenInfo.new(0.7, Enum.EasingStyle.Back, Enum.EasingDirection.Out), { Scale = 1 }):Play()
local word, letters = "Checking...", {}
local holder = mk("Frame", { AnchorPoint = Vector2.new(0.5, 0), Position = UDim2.new(0.5, 0, 0.5, big / 2 - 4), Size = UDim2.fromOffset(#word * 15, 30), BackgroundTransparency = 1, ZIndex = 51 }, LS)
for i = 1, #word do
	letters[i] = mk("TextLabel", { Size = UDim2.fromOffset(15, 26), Position = UDim2.fromOffset((i - 1) * 15, 0), BackgroundTransparency = 1, Text = word:sub(i, i), Font = Enum.Font.GothamBold, TextSize = 19, TextColor3 = Color3.new(1, 1, 1), ZIndex = 51 }, holder)
end
local lpct = lbl(LS, "0%", 12, C.dim, UDim2.new(0.5, -80, 0.5, big / 2 + 30), UDim2.fromOffset(160, 16), Enum.Font.Gotham, Enum.TextXAlignment.Center)
lpct.ZIndex = 51
local lsT0 = os.clock()
local lsConn = RS.RenderStepped:Connect(function()
	local t = os.clock() - lsT0
	for i, l in ipairs(letters) do l.Position = UDim2.fromOffset((i - 1) * 15, math.sin(t * 5 - i * 0.65) * 6) end
	lpct.Text = math.min(100, math.floor(t / 1.6 * 100)) .. "%"
end)

-- ===== card (landscape rectangle) =====
local CW, CH, TB = 560, 262, 42
local card = mk("Frame", { AnchorPoint = Vector2.new(0.5, 0.5), Position = UDim2.fromScale(0.5, 0.5), Size = UDim2.fromOffset(CW, CH), BackgroundColor3 = C.card, BorderSizePixel = 0, Active = true, Draggable = true, ClipsDescendants = true, Visible = false }, sg)
corner(card, 14)
mk("UIScale", { Scale = math.min(1, (vpS.X * 0.92) / CW, (vpS.Y * 0.88) / CH) }, card)
local stroke = mk("UIStroke", { Color = C.red, Thickness = 1.5, Transparency = 0.2 }, card)
grad(stroke, C.red, C.white, 20)
local bar = mk("Frame", { Size = UDim2.new(1, 0, 0, TB), BackgroundColor3 = C.bg, BorderSizePixel = 0 }, card)
markImg(bar, 30, UDim2.fromOffset(10, 6), 3)
wordmark(bar, UDim2.fromOffset(46, 7), 20, 3)
local function tbtn(txt, x, col, vis)
	local b = mk("TextButton", { Text = txt, Font = Enum.Font.GothamBlack, TextSize = 17, TextColor3 = C.txt, BackgroundColor3 = col, Position = UDim2.new(1, x, 0, 8), Size = UDim2.fromOffset(28, 26), AutoButtonColor = false, Visible = vis, BorderSizePixel = 0, ZIndex = 4 }, bar)
	corner(b, 7)
	return b
end
local minB, closeB = tbtn("-", -68, C.line, true), tbtn("X", -36, C.deep, false)
local body = mk("Frame", { Position = UDim2.fromOffset(0, TB), Size = UDim2.new(1, 0, 1, -TB), BackgroundTransparency = 1 }, card)
local pick = mk("Frame", { Size = UDim2.fromScale(1, 1), BackgroundTransparency = 1 }, body)
local wait = mk("Frame", { Size = UDim2.fromScale(1, 1), BackgroundTransparency = 1, Visible = false }, body)
local PAD, LW, RX, RW = 14, 250, 280, 266

-- ----- pick page: left = executor + recommendation, right = choices -----
lbl(pick, "Executor", 11, C.dim, UDim2.fromOffset(PAD, 10), UDim2.fromOffset(120, 14))
lbl(pick, EX.name, 17, C.txt, UDim2.fromOffset(PAD, 24), UDim2.fromOffset(LW, 22), Enum.Font.GothamBold)
local chip = mk("TextLabel", { Position = UDim2.fromOffset(PAD, 52), Size = UDim2.fromOffset(96, 22), BackgroundColor3 = EX.weak and C.amber or C.ok, Text = EX.weak and "LIMITED" or "SUPPORTED", Font = Enum.Font.GothamBlack, TextSize = 11, TextColor3 = Color3.fromRGB(14, 18, 26) }, pick)
corner(chip, 11)
local info = "Device: " .. (EX.mobile and "Mobile" or "PC")
if #EX.miss > 0 then info = info .. "   Missing: " .. table.concat(EX.miss, ", ") end
lbl(pick, info, 10, C.dim, UDim2.fromOffset(PAD, 80), UDim2.fromOffset(LW, 40), Enum.Font.Gotham)
local recBox = mk("Frame", { Position = UDim2.fromOffset(PAD, 126), Size = UDim2.fromOffset(LW, 80), BackgroundColor3 = C.panel, BorderSizePixel = 0 }, pick)
corner(recBox, 10)
lbl(recBox, "Recommended: " .. rec, 13, C.amber, UDim2.fromOffset(10, 8), UDim2.new(1, -20, 0, 16), Enum.Font.GothamBold)
lbl(recBox, why, 10, C.dim, UDim2.fromOffset(10, 28), UDim2.new(1, -20, 0, 48), Enum.Font.Gotham)
local function pbtn(kind, y, sub)
	local isRec = kind == rec
	local b = mk("TextButton", { Position = UDim2.fromOffset(RX, y), Size = UDim2.fromOffset(RW, 54), BackgroundColor3 = isRec and C.red or C.panel, Text = "", AutoButtonColor = false, BorderSizePixel = 0 }, pick)
	corner(b, 10)
	if isRec then grad(b, C.red, C.deep, 0) else mk("UIStroke", { Color = C.line, Thickness = 1 }, b) end
	lbl(b, kind:upper() .. (isRec and "   (recommended)" or ""), 14, C.white, UDim2.fromOffset(14, 7), UDim2.new(1, -28, 0, 18), Enum.Font.GothamBlack)
	lbl(b, sub, 10, isRec and Color3.fromRGB(255, 220, 224) or C.dim, UDim2.fromOffset(14, 28), UDim2.new(1, -28, 0, 22), Enum.Font.Gotham)
	return b
end
local bO = pbtn("Original", 10, "All features. Needs a strong executor.")
local bL = pbtn("Lite", 72, "Lighter. Works on phones and weaker executors.")
if EX.weak then
	local realB = mk("TextButton", { Position = UDim2.fromOffset(RX, 136), Size = UDim2.fromOffset(RW, 40), BackgroundColor3 = C.card, Text = "Get Real Executor: projectreal.gg\n(tap to copy the link)", Font = Enum.Font.GothamBold, TextSize = 11, TextColor3 = C.amber, AutoButtonColor = false, BorderSizePixel = 0 }, pick)
	corner(realB, 9)
	mk("UIStroke", { Color = C.amber, Thickness = 1, Transparency = 0.5 }, realB)
	realB.MouseButton1Click:Connect(function()
		local ok = pcall(function() if setclipboard then setclipboard(REAL_URL) elseif toclipboard then toclipboard(REAL_URL) else error("x") end end)
		realB.Text = ok and "Link copied. Paste it in your browser." or REAL_URL
	end)
else
	lbl(pick, "Tip: if Original does not appear within 10 seconds, the loader offers Lite.", 10, C.dim, UDim2.fromOffset(RX, 140), UDim2.fromOffset(RW, 30), Enum.Font.Gotham)
end

-- ----- wait page: left = skeleton + progress, right = status / offers -----
local sk = mk("Frame", { Position = UDim2.fromOffset(PAD, 12), Size = UDim2.fromOffset(LW, 120), BackgroundColor3 = Color3.fromRGB(34, 38, 52), BorderSizePixel = 0, ClipsDescendants = true }, wait)
corner(sk, 10)
local shim = mk("Frame", { Size = UDim2.fromScale(1, 1), BackgroundColor3 = Color3.new(1, 1, 1), BorderSizePixel = 0 }, sk)
local sgr = mk("UIGradient", { Rotation = 20, Transparency = NumberSequence.new({ NumberSequenceKeypoint.new(0, 1), NumberSequenceKeypoint.new(0.35, 1), NumberSequenceKeypoint.new(0.5, 0.82), NumberSequenceKeypoint.new(0.65, 1), NumberSequenceKeypoint.new(1, 1) }) }, shim)
local stT = lbl(wait, "Starting...", 12, C.txt, UDim2.fromOffset(PAD, 142), UDim2.fromOffset(LW - 50, 32), Enum.Font.GothamBold)
local pcT = lbl(wait, "0%", 12, C.txt, UDim2.fromOffset(PAD + LW - 48, 142), UDim2.fromOffset(48, 16), Enum.Font.GothamBlack, Enum.TextXAlignment.Right)
local track = mk("Frame", { Position = UDim2.fromOffset(PAD, 182), Size = UDim2.fromOffset(LW, 8), BackgroundColor3 = Color3.fromRGB(34, 38, 52), BorderSizePixel = 0 }, wait)
corner(track, 4)
local fill = mk("Frame", { Size = UDim2.fromScale(0, 1), BackgroundColor3 = C.red, BorderSizePixel = 0 }, track)
corner(fill, 4)
local fillG = grad(fill, C.red, C.white, 0)
local panel = mk("Frame", { Position = UDim2.fromOffset(RX, 12), Size = UDim2.fromOffset(RW, 192), BackgroundColor3 = C.bg, BorderSizePixel = 0 }, wait)
corner(panel, 10)
local offT = lbl(panel, "", 11, C.txt, UDim2.fromOffset(10, 8), UDim2.fromOffset(RW - 20, 112), Enum.Font.GothamMedium)
local function obtn(txt, x, y, w, col, tc)
	local b = mk("TextButton", { Position = UDim2.fromOffset(x, y), Size = UDim2.fromOffset(w, 32), BackgroundColor3 = col, Text = txt, Font = Enum.Font.GothamBold, TextSize = 12, TextColor3 = tc or C.txt, AutoButtonColor = false, BorderSizePixel = 0, Visible = false }, panel)
	corner(b, 9)
	return b
end
local offA = obtn("Run Lite", 10, 124, 117, C.red, C.white)
local offB = obtn("Keep waiting", 133, 124, 123, C.line)
local offC = obtn("Copy error", 10, 158, RW - 20, C.panel)

-- ===== state + logic =====
local S = { kind = nil, pct = 0, t0 = 0, done = false, err = nil, gen = 0 }
local snap, roots = {}, {}
pcall(function() local h = gethui() if typeof(h) == "Instance" then roots[#roots + 1] = h end end)
pcall(function() roots[#roots + 1] = game:GetService("CoreGui") end)
pcall(function() roots[#roots + 1] = Players.LocalPlayer:FindFirstChildOfClass("PlayerGui") end)
local function snapshot()
	snap = {}
	for _, r in ipairs(roots) do for _, c in ipairs(r:GetChildren()) do snap[c] = true end end
end
local function ready()
	if genv.__rvReady or _G.__rvReady then return true end
	for _, r in ipairs(roots) do
		for _, c in ipairs(r:GetChildren()) do
			if c:IsA("ScreenGui") and c ~= sg and not snap[c] and #c:GetDescendants() > 250 then return true end
		end
	end
	return false
end
local function setP(p, t)
	S.pct = math.clamp(p, 0, 100)
	fill.Size = UDim2.fromScale(S.pct / 100, 1)
	pcT.Text = math.floor(S.pct + 0.5) .. "%"
	if t then stT.Text = t end
end
local function copy(t) return pcall(function() if setclipboard then setclipboard(t) elseif toclipboard then toclipboard(t) else error("x") end end) end
local function runScript(kind)
	local okG, src = pcall(function() return game:HttpGet(URLS[kind]) end)
	if not okG or type(src) ~= "string" or #src < 50 then S.err = "Download failed:\n" .. tostring(src):sub(1, 200) return end
	local fn, err = loadstring(src)
	if not fn then S.err = "Compile error:\n" .. tostring(err):sub(1, 300) return end
	local ok2, e2 = xpcall(fn, function(e) return tostring(e) .. "\n" .. tostring(debug.traceback()):sub(1, 260) end)
	if not ok2 then S.err = "Runtime error (" .. kind .. "):\n" .. tostring(e2):sub(1, 420) end
end
local function showPanel(text, a, b, c)
	offT.Text = text
	offA.Visible, offB.Visible, offC.Visible = a or false, b or false, c or false
end
local function launch(kind)
	S.gen += 1
	local g = S.gen
	S.kind, S.err, S.done = kind, nil, false
	genv.__rvReady = nil _G.__rvReady = nil
	S.t0 = os.clock()
	snapshot()
	pick.Visible, wait.Visible = false, true
	sk.Visible = true
	fill.BackgroundColor3 = C.red fillG.Enabled = true
	closeB.Visible = false
	setP(0, "Running " .. kind .. "...")
	showPanel("Waiting for the script to appear on screen.\n\n" .. (kind == "Original" and "If Original does not appear within 10 seconds, you can switch to Lite." or "This can take a few seconds on a phone."))
	task.spawn(runScript, kind)
	task.spawn(function()
		local offered = false
		while sg.Parent and S.gen == g and not S.done do
			local el = os.clock() - S.t0
			if ready() then
				S.done = true
				setP(100, "Script appeared")
				fill.BackgroundColor3 = C.ok fillG.Enabled = false
				sk.Visible = false
				closeB.Visible = true
				showPanel(kind .. " is running.\n\nUse - to minimize this window, or X to close it.")
				break
			end
			if S.err then
				setP(S.pct, "Failed to start")
				fill.BackgroundColor3 = C.bad fillG.Enabled = false
				closeB.Visible = true
				if not offered then
					offered = true
					local okc = copy(S.err)
					offA.Text = "Run Lite"
					offB.Text = "Back"
					showPanel(S.err .. (okc and "\n\n(error copied to clipboard)" or ""), kind == "Original", true, true)
					offT.Size = UDim2.fromOffset(RW - 20, 116)
				end
			else
				setP(math.max(S.pct, 92 * (1 - math.exp(-el / 5))), "Waiting for " .. kind .. " to appear...")
				if kind == "Original" and el > 10 and not offered then
					offered = true
					offB.Text = "Keep waiting"
					showPanel("Original has not appeared after 10 seconds. Your executor may be too weak for it.\n\nRun Lite instead?", true, true)
				elseif kind == "Lite" and el > 25 and not offered then
					offered = true
					offB.Text = "Keep waiting"
					showPanel("Lite has not appeared after 25 seconds. This executor probably can't run RenVersal.\n\nTry Real Executor (projectreal.gg).", false, true)
				end
			end
			task.wait(0.35)
		end
	end)
end
bO.MouseButton1Click:Connect(function() launch("Original") end)
bL.MouseButton1Click:Connect(function() launch("Lite") end)
offA.MouseButton1Click:Connect(function() launch("Lite") end)
offB.MouseButton1Click:Connect(function()
	if S.err then
		S.gen += 1
		wait.Visible, pick.Visible = false, true
	else
		showPanel("Still waiting for " .. tostring(S.kind) .. " to appear...")
	end
end)
offC.MouseButton1Click:Connect(function() offC.Text = copy(tostring(S.err or "")) and "Copied" or "Copy failed" end)

-- minimize (always) / close (after the script appeared or failed)
local mini = false
minB.MouseButton1Click:Connect(function()
	mini = not mini
	body.Visible = not mini
	minB.Text = mini and "+" or "-"
	card.Size = mini and UDim2.fromOffset(CW, TB) or UDim2.fromOffset(CW, CH)
end)
closeB.MouseButton1Click:Connect(function()
	pcall(function() lsConn:Disconnect() end)
	sg:Destroy()
end)

-- shimmer (skeleton + wordmark)
RS.RenderStepped:Connect(function()
	if not sg.Parent then return end
	local t = os.clock()
	if sk.Visible and wait.Visible then sgr.Offset = Vector2.new(((t * 0.8) % 2) - 1, 0) end
	for i, g in ipairs(shiny) do g.Offset = Vector2.new(((t * 0.6 + i * 0.35) % 2) - 1, 0) end
end)

-- ===== finish loading screen, show card =====
task.delay(1.7, function()
	pcall(function() lsConn:Disconnect() end)
	TS:Create(LS, TweenInfo.new(0.4), { BackgroundTransparency = 1 }):Play()
	TS:Create(lscale, TweenInfo.new(0.4, Enum.EasingStyle.Back, Enum.EasingDirection.In), { Scale = 0.01 }):Play()
	for _, l in ipairs(letters) do TS:Create(l, TweenInfo.new(0.3), { TextTransparency = 1 }):Play() end
	task.wait(0.45)
	pcall(function() LS:Destroy() end)
	card.Visible = true
end)
