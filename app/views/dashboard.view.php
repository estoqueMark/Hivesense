<?php include "includes/hivesense-header.php"; ?>
<!-- Dashboard View CSS -->
<link rel="stylesheet" href="<?= ROOT ?>/public/assets/css/dashboard.css">

<div class="app-container">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo">
            <div class="logo-icon">
                <img src="<?= ROOT ?>/public/assets/img/cordillera-apiculture-logo.png" alt="Cordillera Regional Apiculture Center">
            </div>
            <h2>HiveSense</h2>
        </div>
        <div class="sidebar-section-label">
            <span>MY HIVES</span>
            <button class="btn-icon-sm" onclick="openHiveManager()" title="Manage Hives">
                <i class="fas fa-cog"></i>
            </button>
        </div>
        
        <div class="hive-list" id="hiveList">
            <?php if (!empty($hives)): ?>
                <?php foreach ($hives as $index => $hive): ?>
                    <div class="hive-item <?php echo $index === 0 ? 'active' : ''; ?>" 
                         data-id="<?php echo $hive['sensor_id']; ?>" 
                         onclick="selectHive(<?php echo $hive['sensor_id']; ?>, this)">
                        <div class="hive-icon"><i class="fas fa-bezier-curve"></i></div>
                        <div class="hive-info">
                            <h4><?php echo htmlspecialchars($hive['hive_name']); ?></h4>
                            <p><?php echo htmlspecialchars($hive['location'] ?? 'No location set'); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color:var(--text-muted);font-size:0.82rem;padding:1rem;text-align:center;">No active hives.<br>Add one via the ⚙ icon.</p>
            <?php endif; ?>
        </div>
        
        <div class="sidebar-footer">
            <?php if (in_array($_SESSION['role'] ?? '', ['admin','apiarist'])): ?>
            <button class="btn-calendar-trigger" onclick="openCalendar()">
                <i class="fas fa-calendar-alt"></i>
                <span>Inspection Calendar</span>
            </button>
            <?php endif; ?>
            <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
            <button class="btn-calendar-trigger" onclick="openAnnouncementManager()" style="margin-top:4px;">
                <i class="fas fa-bullhorn"></i>
                <span>Announcements</span>
            </button>
            <?php endif; ?>
            <div class="system-status">
                <span class="status-dot"></span>
                <span>System Online</span>
            </div>
            <div class="last-update" id="lastUpdateText">Last update: --</div>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content">
        <div class="header">
            <h1 id="selectedHiveTitle">HiveSense Dashboard</h1>
            <div class="header-actions">
                <button class="btn-refresh" onclick="refreshData()">
                    <i class="fas fa-sync-alt"></i>
                    <span>Refresh</span>
                </button>
                <?php if (in_array($_SESSION['role'] ?? '', ['admin','apiarist'])): ?>
                <div class="alert-panel-wrap" id="alertPanelWrap">
                    <button class="btn-icon-bell" onclick="toggleAlertPanel()" title="Alerts">
                        <i class="fas fa-bell"></i>
                        <span class="alert-badge" id="alertBadge" style="display:none;">0</span>
                    </button>
                    <div class="alert-panel" id="alertPanel">
                        <div class="alert-panel-header">
                            <span>Active Alerts</span>
                            <button class="alert-history-btn" onclick="openAlertHistory()" title="View full history">
                                <i class="fas fa-history"></i> History
                            </button>
                        </div>
                        <div class="alert-list" id="alertList">
                            <div class="alert-empty">No active alerts.</div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <div class="user-menu" id="userMenuWrap">
                    <div class="user-chip" onclick="toggleUserMenu()">
                        <div class="user-avatar"><i class="fas fa-user"></i></div>
                        <span class="user-name"><?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User') ?></span>
                        <i class="fas fa-chevron-down user-chevron"></i>
                    </div>
                    <div class="user-dropdown" id="userDropdown">
                        <div class="user-dropdown-info">
                            <div class="ud-name"><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></div>
                            <div class="ud-role"><?= ucfirst(htmlspecialchars($_SESSION['role'] ?? 'viewer')) ?></div>
                        </div>
                        <div class="user-dropdown-divider"></div>
                        <button class="ud-item" onclick="openChangePassword()">
                            <i class="fas fa-key"></i> Change Password
                        </button>
                        <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                        <button class="ud-item" onclick="openUserManager()">
                            <i class="fas fa-users"></i> Manage Users
                        </button>
                        <?php endif; ?>
                        <div class="user-dropdown-divider"></div>
                        <a class="ud-item ud-logout" href="<?= ROOT ?>/logout">
                            <i class="fas fa-sign-out-alt"></i> Sign Out
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Temperature & Humidity Section -->
        <div class="th-section">
            <div class="th-section-label">
                <i class="fas fa-thermometer-half"></i>
                Temperature &amp; Humidity Monitor
            </div>
            <div class="th-grid">
                <!-- Temperature current -->
                <div class="reading-card temperature">
                    <div class="card-icon"><i class="fas fa-thermometer-half"></i></div>
                    <div class="card-content">
                        <span class="card-label">Current Temperature</span>
                        <div><span class="card-value" id="currentTemp">--</span><span class="card-unit">°C</span></div>
                        <span class="card-footer" id="tempTime">--</span>
                    </div>
                    <div class="th-status-badge temp-badge" id="tempStatusBadge">—</div>
                </div>

                <!-- Humidity current -->
                <div class="reading-card humidity">
                    <div class="card-icon"><i class="fas fa-tint"></i></div>
                    <div class="card-content">
                        <span class="card-label">Current Humidity</span>
                        <div><span class="card-value" id="currentHumidity">--</span><span class="card-unit">%</span></div>
                        <span class="card-footer" id="humidityTime">--</span>
                    </div>
                    <div class="th-status-badge hum-badge" id="humidityStatusBadge">—</div>
                </div>

                <!-- Today min temp -->
                <div class="reading-card min-temp">
                    <div class="card-icon"><i class="fas fa-arrow-down"></i></div>
                    <div class="card-content">
                        <span class="card-label">Today's Minimum</span>
                        <div><span class="card-value" id="minToday">--</span><span class="card-unit">°C</span></div>
                        <span class="card-footer">Lowest temperature today</span>
                    </div>
                </div>

                <!-- Today max temp -->
                <div class="reading-card max-temp">
                    <div class="card-icon"><i class="fas fa-arrow-up"></i></div>
                    <div class="card-content">
                        <span class="card-label">Today's Maximum</span>
                        <div><span class="card-value" id="maxToday">--</span><span class="card-unit">°C</span></div>
                        <span class="card-footer">Highest temperature today</span>
                    </div>
                </div>

                <!-- Temperature guide -->
                <div class="th-scale-card temp-scale-card">
                    <div class="th-scale-title"><i class="fas fa-info-circle"></i> Temperature Guide</div>
                    <div class="th-scale-row"><span class="th-dot cold"></span><span class="th-scale-range">&lt; 32 °C</span><span class="th-scale-label">Cold — Colony at risk</span></div>
                    <div class="th-scale-row"><span class="th-dot optimal"></span><span class="th-scale-range">32–36 °C</span><span class="th-scale-label">Optimal — Brood zone</span></div>
                    <div class="th-scale-row"><span class="th-dot warm"></span><span class="th-scale-range">36–38 °C</span><span class="th-scale-label">Warm — Monitor closely</span></div>
                    <div class="th-scale-row"><span class="th-dot hot"></span><span class="th-scale-range">&gt; 38 °C</span><span class="th-scale-label">Hot — Check ventilation</span></div>
                </div>

                <!-- Humidity guide -->
                <div class="th-scale-card hum-scale-card">
                    <div class="th-scale-title"><i class="fas fa-info-circle"></i> Humidity Guide</div>
                    <div class="th-scale-row"><span class="th-dot dry"></span><span class="th-scale-range">&lt; 50 %</span><span class="th-scale-label">Dry — Risk of comb damage</span></div>
                    <div class="th-scale-row"><span class="th-dot hum-optimal"></span><span class="th-scale-range">50–70 %</span><span class="th-scale-label">Optimal — Healthy hive</span></div>
                    <div class="th-scale-row"><span class="th-dot humid"></span><span class="th-scale-range">70–85 %</span><span class="th-scale-label">Humid — Watch for mold</span></div>
                    <div class="th-scale-row"><span class="th-dot very-humid"></span><span class="th-scale-range">&gt; 85 %</span><span class="th-scale-label">Very Humid — Act now</span></div>
                </div>
            </div>
        </div>

        <!-- CO2 Section — visually distinct from temp/humidity -->
        <div class="co2-section">
            <div class="co2-section-label">
                <i class="fas fa-wind"></i>
                Air Quality Monitor
            </div>
            <div class="co2-grid">
                <div class="reading-card co2-current">
                    <div class="card-icon co2-icon"><i class="fas fa-cloud"></i></div>
                    <div class="card-content">
                        <span class="card-label">CO₂ Concentration</span>
                        <div><span class="card-value" id="currentCo2">--</span><span class="card-unit">ppm</span></div>
                        <span class="card-footer" id="co2Time">--</span>
                    </div>
                    <div class="co2-status-badge" id="co2StatusBadge">—</div>
                </div>
                <div class="reading-card co2-min">
                    <div class="card-icon co2-icon-dim"><i class="fas fa-arrow-down"></i></div>
                    <div class="card-content">
                        <span class="card-label">Today's CO₂ Min</span>
                        <div><span class="card-value" id="minCo2">--</span><span class="card-unit">ppm</span></div>
                        <span class="card-footer">Lowest CO₂ today</span>
                    </div>
                </div>
                <div class="reading-card co2-max">
                    <div class="card-icon co2-icon-warn"><i class="fas fa-arrow-up"></i></div>
                    <div class="card-content">
                        <span class="card-label">Today's CO₂ Max</span>
                        <div><span class="card-value" id="maxCo2">--</span><span class="card-unit">ppm</span></div>
                        <span class="card-footer">Highest CO₂ today</span>
                    </div>
                </div>
                <div class="co2-scale-card">
                    <div class="co2-scale-title"><i class="fas fa-info-circle"></i> CO₂ Level Guide</div>
                    <div class="co2-scale-row"><span class="co2-dot good"></span><span class="co2-scale-range">300–700 ppm</span><span class="co2-scale-label">Good — Normal hive</span></div>
                    <div class="co2-scale-row"><span class="co2-dot moderate"></span><span class="co2-scale-range">700–1500 ppm</span><span class="co2-scale-label">Moderate — Monitor closely</span></div>
                    <div class="co2-scale-row"><span class="co2-dot high"></span><span class="co2-scale-range">1500–3000 ppm</span><span class="co2-scale-label">High — Check ventilation</span></div>
                    <div class="co2-scale-row"><span class="co2-dot danger"></span><span class="co2-scale-range">&gt;3000 ppm</span><span class="co2-scale-label">Critical — Act immediately</span></div>
                </div>
            </div>
        </div>

        <!-- Food Store Section -->
        <div class="food-section">
            <div class="food-section-label">
                <i class="fas fa-utensils"></i>
                Food Store Monitor
            </div>
            <div class="food-grid">
                <div class="reading-card food-current">
                    <div class="card-icon food-icon"><i class="fas fa-jar"></i></div>
                    <div class="card-content">
                        <span class="card-label">Current Food Level</span>
                        <div><span class="card-value" id="currentFood">--</span><span class="card-unit">%</span></div>
                        <span class="card-footer" id="foodTime">--</span>
                    </div>
                    <div class="food-status-badge" id="foodStatusBadge">—</div>
                </div>
                <div class="food-scale-card">
                    <div class="food-scale-title"><i class="fas fa-info-circle"></i> Food Level Guide</div>
                    <div class="food-scale-row"><span class="food-dot full"></span><span class="food-scale-range">100%</span><span class="food-scale-label">Full — Well stocked</span></div>
                    <div class="food-scale-row"><span class="food-dot moderate"></span><span class="food-scale-range">50%</span><span class="food-scale-label">Moderate — Plan feeding soon</span></div>
                    <div class="food-scale-row"><span class="food-dot empty"></span><span class="food-scale-range">0%</span><span class="food-scale-label">Empty — Feed now</span></div>
                </div>
            </div>
        </div>
        
        <!-- Chart Section -->
        <div class="chart-section">
            <div class="section-header">
                <h3><i class="fas fa-chart-line"></i> Sensor History</h3>
                <div class="chart-controls">
                    <div class="chart-mode-group">
                        <button class="chart-btn active" id="modeLive" onclick="setChartMode('live', this)">
                            <i class="fas fa-circle" style="color:#22c55e;font-size:0.55rem;vertical-align:middle;"></i> Live
                        </button>
                        <button class="chart-btn" id="modeSummary" onclick="setChartMode('summary', this)">Summary</button>
                    </div>
                    <div class="chart-range-group" id="liveRangeGroup">
                        <button class="chart-btn active" data-hours="1"   onclick="setLiveHours(1, this)">1 Hr</button>
                        <button class="chart-btn"        data-hours="6"   onclick="setLiveHours(6, this)">6 Hr</button>
                        <button class="chart-btn"        data-hours="24"  onclick="setLiveHours(24, this)">24 Hr</button>
                        <button class="chart-btn"        data-hours="168" onclick="setLiveHours(168, this)">7 Days</button>
                    </div>
                    <div class="chart-range-group" id="summaryRangeGroup" style="display:none;">
                        <button class="chart-btn"        data-days="7"  onclick="setSummaryDays(7, this)">7 Days</button>
                        <button class="chart-btn"        data-days="14" onclick="setSummaryDays(14, this)">14 Days</button>
                        <button class="chart-btn active" data-days="30" onclick="setSummaryDays(30, this)">30 Days</button>
                    </div>
                </div>
            </div>
            <div class="chart-mode-label" id="chartModeLabel">
                <i class="fas fa-circle" style="color:#22c55e;font-size:0.6rem;vertical-align:middle;"></i>
                Showing every individual reading — real-time monitoring view
            </div>
            <div class="chart-container">
                <canvas id="historyChart"></canvas>
            </div>
        </div>

        <!-- Table Section — two tabs -->
        <div class="table-section">
            <div class="section-header">
                <h3><i class="fas fa-table"></i> Data Log</h3>
                <div class="tab-group">
                    <button class="tab-btn active" id="tabReadings" onclick="switchTab('readings')">
                        <i class="fas fa-list"></i> Individual Readings
                    </button>
                    <button class="tab-btn" id="tabSummary" onclick="switchTab('summary')">
                        <i class="fas fa-calendar-day"></i> Daily Summary
                    </button>
                </div>
            </div>

            <!-- Individual Readings -->
            <div id="panelReadings">
                <div class="table-toolbar">
                    <span class="table-count" id="readingsCount">—</span>
                    <select class="table-filter" id="readingsHourFilter" onchange="loadReadingsTable()">
                        <option value="1">Last 1 hour</option>
                        <option value="6">Last 6 hours</option>
                        <option value="24" selected>Last 24 hours</option>
                        <option value="168">Last 7 days</option>
                    </select>
                </div>
                <div class="table-container" style="overflow-x:auto;-webkit-overflow-scrolling:touch;">
                    <table style="min-width:640px;">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Temperature</th>
                                <th>Humidity</th>
                                <th>CO₂</th>
                                <th>Temp Status</th>
                            </tr>
                        </thead>
                        <tbody id="readingsTableBody">
                            <tr><td colspan="7" class="loading">Loading readings...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Daily Summary -->
            <div id="panelSummary" style="display:none;">
                <div class="table-toolbar">
                    <span class="table-count" id="summaryCount">—</span>
                </div>
                <div class="table-container" style="overflow-x:auto;-webkit-overflow-scrolling:touch;">
                    <table style="min-width:640px;">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Avg Temp</th>
                                <th>Avg Humidity</th>
                                <th>Max Temp</th>
                                <th>Min Temp</th>
                                <th>Avg CO₂</th>
                                <th>Max CO₂</th>
                                <th>Readings</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <tr><td colspan="8" class="loading">Loading data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Hive Manager Modal -->
<div id="hiveManagerOverlay" class="modal-overlay">
    <div class="modal modal-hive">
        <div class="modal-header">
            <h2><i class="fas fa-database"></i> Hive Manager</h2>
            <button class="modal-close" onclick="closeHiveManager()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="hive-form-section">
                <h4 id="hiveFormTitle"><i class="fas fa-plus"></i> Add New Hive</h4>
                <input type="hidden" id="editHiveId" value="">
                <div class="form-group">
                    <label>Hive Name <span class="required">*</span></label>
                    <input type="text" id="hiveNameInput" placeholder="e.g., East Apiary">
                </div>
                <div class="form-group">
                    <label>Location</label>
                    <input type="text" id="hiveLocationInput" placeholder="e.g., Backyard Garden">
                </div>
                <div class="form-actions">
                    <button class="btn-secondary" id="hiveCancelEdit" style="display:none" onclick="resetHiveForm()">Cancel</button>
                    <button class="btn-primary" onclick="submitHiveForm()">
                        <i class="fas fa-plus"></i>
                        <span id="hiveFormBtnText">Add Hive</span>
                    </button>
                </div>
            </div>
            
            <div class="hive-form-section">
                <h4><i class="fas fa-list"></i> Existing Hives</h4>
                <div id="hiveManagerList" class="hive-manager-list">
                    <div class="loading">Loading hives...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Calendar Modal -->
<div id="calendarOverlay" class="modal-overlay">
    <div class="modal modal-calendar">
        <div class="modal-header">
            <h2><i class="fas fa-calendar-alt"></i> Inspection Calendar</h2>
            <div style="display:flex;gap:8px;align-items:center;">
                <button class="btn-secondary" style="padding:8px 14px;" onclick="openCompareNotes()">
                    <i class="fas fa-code-compare"></i> Compare Notes
                </button>
                <button class="btn-secondary" style="padding:8px 14px;" onclick="exportNotesCsv()">
                    <i class="fas fa-file-csv"></i> Export CSV
                </button>
                <button class="modal-close" onclick="closeCalendar()">&times;</button>
            </div>
        </div>
        <div class="modal-body" style="padding: 0;">
            <div class="calendar-body">
                <!-- Calendar Pane -->
                <div class="calendar-pane">
                    <div class="cal-nav">
                        <button class="cal-nav-btn" onclick="calPrevMonth()">&lt;</button>
                        <span class="cal-month-label" id="calMonthLabel"></span>
                        <button class="cal-nav-btn" onclick="calNextMonth()">&gt;</button>
                    </div>
                    
                    <div class="cal-weekdays">
                        <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
                    </div>
                    
                    <div id="calGrid" class="cal-grid"></div>
                    
                    <!-- Hive Selector -->
                    <div class="form-group">
                        <label><i class="fas fa-hive"></i> Select Hive</label>
                        <select id="insp_hive_id" class="table-filter" onchange="onHiveChange()">
                            <option value="">All Hives</option>
                            <?php if (!empty($hives)): ?>
                                <?php foreach ($hives as $hive): ?>
                                    <option value="<?php echo $hive['sensor_id']; ?>">
                                        <?php echo htmlspecialchars($hive['hive_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <!-- Rating Legend -->
                    <div class="cal-legend">
                        <div class="legend-title">
                            <i class="fas fa-chart-simple"></i> Rating Scale (1-10)
                        </div>
                        <div class="legend-row">
                            <span class="legend-key">1-3:</span>
                            <span class="legend-val">Poor / Low</span>
                        </div>
                        <div class="legend-row">
                            <span class="legend-key">4-7:</span>
                            <span class="legend-val">Good / Medium</span>
                        </div>
                        <div class="legend-row">
                            <span class="legend-key">8-10:</span>
                            <span class="legend-val">Excellent / High</span>
                        </div>
                    </div>
                </div>
                
                <!-- Note Editor Pane -->
                <div id="notePaneEmpty" class="note-pane-empty">
                    <i class="fas fa-calendar-day" style="font-size: 3rem; color: var(--text-dim); opacity: 0.5;"></i>
                    <p>Select a date to view or add inspection notes</p>
                </div>
                
                <div id="noteEditor" class="note-editor" style="display: none;">
                    <input type="hidden" id="noteId">
                    <div class="note-date-header" id="noteDateHeader"></div>
                    
<div class="form-group">
                        <label><i class="fas fa-layer-group"></i> Colony ID </label>
                        <input type="number" id="insp_num_colonies" placeholder="Enter number of colony" min="0" step="1" oninput="sanitizeSignedIntColony(this)">
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label><i class="fas fa-crown"></i> Queen Species/Breed</label>
                            <input type="text" id="insp_queen_species" placeholder="e.g., Italian, Carniolan">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-hourglass-half"></i> Queen Age</label>
                            <input type="text" id="insp_queen_age" placeholder="e.g., 6 months, 1 year">
                        </div>
                    </div>

                    <div class="insp-section-label">Hive Maintenance</div>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label><i class="fas fa-th-large"></i> Comb Frames (+/-)</label>
                            <input type="number" id="insp_comb_frames_change" placeholder="e.g., 2 or -1" step="1" min = -10 max = 10 oninput="sanitizeSignedInt(this) ">
                            <div class="field-hint-sm">Positive = added, negative = removed</div>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-square"></i> Wax Foundation (+/-)</label>
                            <input type="number" id="insp_wax_foundation_change" placeholder="e.g., 3 or -2" step="1" min = -10 max = 10 oninput="sanitizeSignedInt(this)">
                            <div class="field-hint-sm">Positive = added, negative = removed</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-utensils"></i> Feeding</label>
                        <select id="insp_feeding">
                            <option value="">Not recorded</option>
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                    
                    <div class="insp-section-label">Colony Health Ratings (1-10)</div>
                    
                    <div class="form-group">
                        <label>Colony Strength</label>
                        <div class="rating-bar" data-field="insp_colony_strength" data-max="10"></div>
                        <input type="hidden" id="insp_colony_strength">
                    </div>
                    
                    <div class="form-group">
                        <label>Open Brood</label>
                        <div class="rating-bar" data-field="insp_brood_pattern_open" data-max="10"></div>
                        <input type="hidden" id="insp_brood_pattern_open">
                    </div>

                    <div class="form-group">
                        <label>Close Brood</label>
                        <div class="rating-bar" data-field="insp_brood_pattern_close" data-max="10"></div>
                        <input type="hidden" id="insp_brood_pattern_close">
                    </div>

                    <div class="form-group">
                        <label>Brood Patern Type</label>
                        <textarea id="insp_brood_pattern_type" rows="3" placeholder="Observations..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Honey Store</label>
                        <div class="rating-bar" data-field="insp_honey_store" data-max="10"></div>
                        <input type="hidden" id="insp_honey_store">
                    </div>
                    
                    <div class="form-group">
                        <label>Pollen Store</label>
                        <div class="rating-bar" data-field="insp_pollen_store" data-max="10"></div>
                        <input type="hidden" id="insp_pollen_store">
                    </div>
                    
                    <div class="form-group">
                        <label>Temperament</label>
                        <select id="insp_temperament">
                            <option value="">Not recorded</option>
                            <option value="docile">Docile</option>
                            <option value="calm">Calm</option>
                            <option value="defensive">Defensive</option>
                            <option value="aggressive">Aggressive</option>
                            <option value="very_aggressive">Very Aggressive</option>
                        </select>
                    </div>
                                    
                    <div class="insp-section-label">Treatment Information</div>
                    
                    <div class="form-group">
                        <label>Treatment Date</label>
                        <input type="date" id="insp_treatment_date">
                    </div>
                    
                    <div class="form-group">
                        <label>Chemical / Brand Used</label>
                        <input type="text" id="insp_chemical_brand" placeholder="e.g., Apivar, Oxalic Acid">
                    </div>
                    
                    <div class="form-group">
                        <label>Remarks / Notes</label>
                        <textarea id="insp_remarks" rows="3" placeholder="Additional observations..."></textarea>
                    </div>
                    
                    <div class="note-actions">
                        <button class="btn-danger-sm" id="btnDeleteNote" onclick="deleteCurrentNote()" style="display: none;">
                            <i class="fas fa-trash"></i> Delete Note
                        </button>
                        <div style="display: flex; gap: 10px;">
                            <button class="btn-secondary" onclick="closeCalendar()">Close</button>
                            <button class="btn-primary" onclick="saveCurrentNote()">
                                <i class="fas fa-save"></i> Save Inspection
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="toast"></div>

<!-- ── User Manager Modal ───────────────────────────────────────── -->
<div id="userManagerOverlay" class="modal-overlay">
    <div class="modal modal-users">
        <div class="modal-header">
            <h2><i class="fas fa-users"></i> User Manager</h2>
            <button class="modal-close" onclick="closeUserManager()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="hive-form-section">
                <h4 id="userFormTitle"><i class="fas fa-plus"></i> Add New User</h4>
                <input type="hidden" id="editUserId" value="">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="form-group">
                        <label>Username <span class="required">*</span></label>
                        <input type="text" id="userUsernameInput" placeholder="e.g., jdoe">
                    </div>
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" id="userFullNameInput" placeholder="e.g., Juan Dela Cruz">
                    </div>
                    <div class="form-group">
                        <label>Email <span class="required">*</span></label>
                        <input type="email" id="userEmailInput" placeholder="e.g., jdoe@email.com">
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select id="userRoleInput">
                            <option value="viewer">Viewer</option>
                            <option value="apiarist">Apiarist</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="form-group" id="passwordFieldWrap">
                    <label>Password <span class="required">*</span></label>
                    <input type="password" id="userPasswordInput" placeholder="Min. 8 characters">
                </div>
                <div class="form-actions">
                    <button class="btn-secondary" id="userCancelEdit" style="display:none" onclick="resetUserForm()">Cancel</button>
                    <button class="btn-primary" onclick="submitUserForm()">
                        <i class="fas fa-plus"></i> <span id="userFormBtnText">Add User</span>
                    </button>
                </div>
            </div>
            <div class="hive-form-section">
                <h4><i class="fas fa-list"></i> Existing Users</h4>
                <div id="userManagerList" class="user-manager-list">
                    <div class="loading">Loading users...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Change Own Password Modal ───────────────────────────────── -->
<div id="changePwOverlay" class="modal-overlay">
    <div class="modal modal-pw">
        <div class="modal-header">
            <h2><i class="fas fa-key"></i> Change Password</h2>
            <button class="modal-close" onclick="closeChangePassword()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label>Current Password</label>
                <input type="password" id="cpCurrentPw" placeholder="Enter current password">
            </div>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" id="cpNewPw" placeholder="Min. 8 characters">
            </div>
            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" id="cpConfirmPw" placeholder="Repeat new password">
            </div>
            <div class="form-actions">
                <button class="btn-secondary" onclick="closeChangePassword()">Cancel</button>
                <button class="btn-primary" onclick="submitChangePassword()">
                    <i class="fas fa-save"></i> Update Password
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ── Admin Reset Password Modal ──────────────────────────────── -->
<div id="resetPwOverlay" class="modal-overlay">
    <div class="modal modal-pw">
        <div class="modal-header">
            <h2><i class="fas fa-unlock-alt"></i> Reset Password</h2>
            <button class="modal-close" onclick="closeResetPassword()">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="resetPwUserId">
            <p style="font-size:0.85rem;color:var(--text-muted);margin-bottom:16px;">
                Set a new password for <strong id="resetPwUsername"></strong>.
            </p>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" id="resetPwNew" placeholder="Min. 8 characters">
            </div>
            <div class="form-actions">
                <button class="btn-secondary" onclick="closeResetPassword()">Cancel</button>
                <button class="btn-primary" onclick="submitResetPassword()">
                    <i class="fas fa-save"></i> Reset Password
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══ Announcement Manager Modal ══════════════════════════════ -->
<div id="annManagerOverlay" class="modal-overlay">
    <div class="modal" style="width:min(720px,97vw);">
        <div class="modal-header">
            <h2><i class="fas fa-bullhorn"></i> Announcement Manager</h2>
            <button class="modal-close" onclick="closeAnnouncementManager()">&times;</button>
        </div>
        <div class="modal-body">

            <!-- Form -->
            <div class="hive-form-section">
                <h4 id="annFormTitle"><i class="fas fa-plus"></i> Post New Announcement</h4>
                <input type="hidden" id="annEditId">
                <div class="form-group">
                    <label>Title <span class="required">*</span></label>
                    <input type="text" id="annTitle" placeholder="e.g. Upcoming Training on Varroa Treatment">
                </div>
                <div class="form-group">
                    <label>Message <span class="required">*</span></label>
                    <textarea id="annBody" rows="4" placeholder="Write your announcement here..."></textarea>
                </div>
                <div class="form-group">
                    <label>Type</label>
                    <select id="annType">
                        <option value="info">ℹ️ Info — General information</option>
                        <option value="success">✅ Update — Positive news / update</option>
                        <option value="warning">⚠️ Warning — Important notice</option>
                        <option value="alert">🔔 Alert — Urgent announcement</option>
                    </select>
                </div>
                <div class="form-group" id="annActiveWrap" style="display:none;">
                    <label>Visibility</label>
                    <select id="annIsActive">
                        <option value="1">Visible (published)</option>
                        <option value="0">Hidden (draft)</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button class="btn-secondary" id="annCancelEdit" style="display:none" onclick="resetAnnForm()">Cancel</button>
                    <button class="btn-primary" onclick="submitAnnForm()">
                        <i class="fas fa-paper-plane"></i>
                        <span id="annFormBtnText">Post Announcement</span>
                    </button>
                </div>
            </div>

            <!-- List -->
            <div class="hive-form-section">
                <h4><i class="fas fa-list"></i> Posted Announcements</h4>
                <div id="annManagerList">
                    <div class="loading">Loading...</div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Alert History Modal -->
<div id="alertHistoryOverlay" class="modal-overlay">
    <div class="modal" style="width:min(720px,97vw);">
        <div class="modal-header">
            <h2><i class="fas fa-history"></i> Alert History</h2>
            <button class="modal-close" onclick="closeAlertHistory()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="table-toolbar">
                <span class="table-count" id="alertHistoryCount">—</span>
                <select class="table-filter" id="alertHistoryHiveFilter" onchange="loadAlertHistory()">
                    <option value="">All Hives</option>
                    <?php if (!empty($hives)): ?>
                        <?php foreach ($hives as $hive): ?>
                            <option value="<?php echo $hive['sensor_id']; ?>">
                                <?php echo htmlspecialchars($hive['hive_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div id="alertHistoryList">
                <div class="loading">Loading...</div>
            </div>
        </div>
    </div>
</div>

<!-- Compare Notes Modal -->
<div id="compareOverlay" class="modal-overlay">
    <div class="modal" style="width:min(900px,97vw);">
        <div class="modal-header">
            <h2><i class="fas fa-code-compare"></i> Compare Inspections</h2>
            <button class="modal-close" onclick="closeCompareNotes()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-grid-2" style="margin-bottom:20px;">
                <div class="form-group">
                    <label><i class="fas fa-calendar"></i> First Date</label>
                    <select id="compareDateA" class="table-filter" onchange="loadCompareSide('A')"></select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-calendar"></i> Second Date</label>
                    <select id="compareDateB" class="table-filter" onchange="loadCompareSide('B')"></select>
                </div>
            </div>
            <div id="compareEmpty" style="text-align:center;padding:40px;color:var(--text-dim);">
                Select two inspection dates above to compare.
            </div>
            <div id="compareTableWrap" style="display:none;overflow-x:auto;">
                <table class="compare-table">
                    <thead>
                        <tr><th>Field</th><th id="compareHeaderA">—</th><th id="compareHeaderB">—</th></tr>
                    </thead>
                    <tbody id="compareTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Dashboard JS -->

<script>
    const BASE_URL = '<?= ROOT ?>';
    const CSRF_TOKEN = <?= json_encode($_SESSION['csrf_token'] ?? '') ?>;
    <?php if (!empty($roleNotice)): ?>
    window.addEventListener('DOMContentLoaded', function() {
        showToast(<?= json_encode($roleNotice) ?>, 'info');
    });
    <?php endif; ?>
</script>
<script src="<?= ROOT ?>/public/assets/js/dashboard.js" defer></script>
<?php include "includes/hivesense-footer.php"; ?>