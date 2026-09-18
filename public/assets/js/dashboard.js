// Configuration

// Auto-attach CSRF token to every same-origin POST request
(function() {
    const originalFetch = window.fetch;
    window.fetch = function(url, options = {}) {
        if (options.method && options.method.toUpperCase() === 'POST') {
            options.headers = Object.assign({}, options.headers, { 'X-CSRF-Token': CSRF_TOKEN });
        }
        return originalFetch(url, options);
    };
})();

let currentDays = 30;
let chart = null;
let refreshTimer = null;

// FIX: Track currently selected hive sensor_id (null = show first/default)
let currentSensorId = null;

// Calendar Variables
let calYear = new Date().getFullYear();
let calMonth = new Date().getMonth();
let calDotDates = [];
let calSelectedDate = null;

// Initialize on page load
$(document).ready(function() {
    const firstHive = $('.hive-item.active').first();
    if (firstHive.length) {
        currentSensorId = firstHive.data('id') || null;
    }

    loadCurrentData();
    loadHistoryData(currentHours);
    loadReadingsTable();
    initRatingBars();
    loadAlerts();

    refreshTimer = setInterval(function() {
        refreshData();
    }, 30000);
});

// Build a URL with optional sensor_id param, safely joining BASE_URL and path
function apiUrl(path, extraParams) {
    const base = BASE_URL.replace(/\/+$/, '');   // strip any trailing slashes from ROOT
    const params = new URLSearchParams(extraParams || {});
    if (currentSensorId) params.set('sensor_id', currentSensorId);
    const qs = params.toString();
    return base + path + (qs ? '?' + qs : '');
}

// Load current readings
async function loadCurrentData() {
    try {
        // FIX: Pass sensor_id so the correct hive's readings are shown
        const response = await fetch(apiUrl('/api/current'));
        const result = await response.json();
        
        if (result.success && result.data) {
            const d = result.data;
            $('#currentTemp').text(d.temperature ?? '--');
            $('#currentHumidity').text(d.humidity ?? '--');
            $('#minToday').text(d.min_temperature ?? '--');
            $('#maxToday').text(d.max_temperature ?? '--');

            // Temperature status badge
            const temp = parseFloat(d.temperature);
            if (!isNaN(temp)) {
                const tb = $('#tempStatusBadge');
                let tcls, tlabel;
                if      (temp < 32) { tcls = 'cold';    tlabel = 'Cold'; }
                else if (temp < 36) { tcls = 'optimal'; tlabel = 'Optimal'; }
                else if (temp < 38) { tcls = 'warm';    tlabel = 'Warm'; }
                else                { tcls = 'hot';     tlabel = 'Hot'; }
                tb.text(tlabel).removeClass('cold optimal warm hot').addClass(tcls);
            } else {
                $('#tempStatusBadge').text('—').removeClass('cold optimal warm hot');
            }

            // Humidity status badge
            const hum = parseFloat(d.humidity);
            if (!isNaN(hum)) {
                const hb = $('#humidityStatusBadge');
                let hcls, hlabel;
                if      (hum < 50) { hcls = 'dry';        hlabel = 'Dry'; }
                else if (hum < 70) { hcls = 'optimal';    hlabel = 'Optimal'; }
                else if (hum < 85) { hcls = 'humid';      hlabel = 'Humid'; }
                else               { hcls = 'very-humid'; hlabel = 'Very Humid'; }
                hb.text(hlabel).removeClass('dry optimal humid very-humid').addClass(hcls);
            } else {
                $('#humidityStatusBadge').text('—').removeClass('dry optimal humid very-humid');
            }

            // CO2
            const co2 = d.co2 ?? null;
            if (co2 !== null && co2 !== undefined) {
                $('#currentCo2').text(parseFloat(co2).toFixed(1));
                // Status badge
                const badge = $('#co2StatusBadge');
                let cls = '', label = '';
                if      (co2 <= 700)  { cls = 'good';     label = 'Good'; }
                else if (co2 <= 1500) { cls = 'moderate'; label = 'Moderate'; }
                else if (co2 <= 3000) { cls = 'high';     label = 'High'; }
                else                  { cls = 'danger';   label = 'Critical'; }
                badge.text(label).removeClass('good moderate high danger').addClass(cls);
            } else {
                $('#currentCo2').text('--');
                $('#co2StatusBadge').text('—').removeClass('good moderate high danger');
            }
            $('#minCo2').text(d.min_co2 != null ? parseFloat(d.min_co2).toFixed(1) : '--');
            $('#maxCo2').text(d.max_co2 != null ? parseFloat(d.max_co2).toFixed(1) : '--');

            // Food level
            const food = d.food_level ?? null;
            if (food !== null && food !== undefined) {
                $('#currentFood').text(parseFloat(food).toFixed(0));
                const fb = $('#foodStatusBadge');
                let fcls, flabel;
                if      (food >= 87.5) { fcls = 'full';     flabel = 'Full'; }
                else if (food >= 62.5) { fcls = 'good';     flabel = 'Good'; }
                else if (food >= 37.5) { fcls = 'moderate'; flabel = 'Moderate'; }
                else if (food >= 12.5) { fcls = 'low';      flabel = 'Low'; }
                else                   { fcls = 'empty';    flabel = 'Empty'; }
                fb.text(flabel).removeClass('full good moderate low empty').addClass(fcls);
            } else {
                $('#currentFood').text('--');
                $('#foodStatusBadge').text('—').removeClass('full good moderate low empty');
            }

            if (d.measurement_date && d.measurement_time) {
                const timeStr = `${d.measurement_date} ${d.measurement_time}`;
                $('#tempTime').text(`Updated: ${timeStr}`);
                $('#humidityTime').text(`Updated: ${timeStr}`);
                $('#co2Time').text(`Updated: ${timeStr}`);
                $('#foodTime').text(`Updated: ${timeStr}`);
                $('#lastUpdateText').text(`Last update: ${timeStr}`);
            }
        }
    } catch (e) {
        console.error('loadCurrentData error:', e);
    }
}

// ── Chart mode state ──────────────────────────────────────────
let chartMode    = 'live';
let currentHours = 1;

function setChartMode(mode, btn) {
    chartMode = mode;
    $('#modeLive, #modeSummary').removeClass('active');
    $(btn).addClass('active');
    if (mode === 'live') {
        $('#liveRangeGroup').show();
        $('#summaryRangeGroup').hide();
        $('#chartModeLabel').html('<i class="fas fa-circle" style="color:#22c55e;font-size:0.6rem;vertical-align:middle;"></i> Showing every individual reading — real-time monitoring view');
        loadHistoryData(currentHours);
    } else {
        $('#liveRangeGroup').hide();
        $('#summaryRangeGroup').show();
        $('#chartModeLabel').html('<i class="fas fa-calendar-day" style="font-size:0.75rem;vertical-align:middle;color:var(--text-dim);"></i> Showing daily averages — long-term trend view');
        loadSummaryChart(currentDays);
    }
}

function setLiveHours(hours, btn) {
    currentHours = hours;
    $('#liveRangeGroup .chart-btn').removeClass('active');
    $(btn).addClass('active');
    loadHistoryData(hours);
}

function setSummaryDays(days, btn) {
    currentDays = days;
    $('#summaryRangeGroup .chart-btn').removeClass('active');
    $(btn).addClass('active');
    loadSummaryChart(days);
}

// Live mode — fetch every individual reading
async function loadHistoryData(hours) {
    try {
        const response = await fetch(apiUrl('/api/readings', { hours: hours || 1 }));
        const result   = await response.json();
        if (result.success && result.data && result.data.length > 0) {
            renderChartLive(result.data);
        } else {
            if (chart) chart.destroy();
        }
    } catch (e) { console.error('loadHistoryData error:', e); }
}

// Summary mode — fetch daily averages
async function loadSummaryChart(days) {
    try {
        const response = await fetch(apiUrl('/api/history', { days }));
        const result   = await response.json();
        if (result.success && result.data && result.data.length > 0) {
            renderChartSummary(result.data);
        }
    } catch (e) { console.error('loadSummaryChart error:', e); }
}

// Render — individual readings
function renderChartLive(data) {
    const ctx = document.getElementById('historyChart').getContext('2d');
    if (chart) chart.destroy();
    const hasCo2 = data.some(d => d.co2 != null && parseFloat(d.co2) > 0);
    const labels  = data.map(d => d.measurement_time ? d.measurement_time.slice(0,5) : d.timestamp);
    const datasets = [{
        label: 'Temperature (°C)',
        data: data.map(d => parseFloat(d.temperature)),
        borderColor: '#2D7A3A', backgroundColor: 'rgba(45,122,58,0.06)',
        borderWidth: 2, pointRadius: 2, pointHoverRadius: 5,
        tension: 0.3, fill: true, yAxisID: 'y-temperature'
    }, {
        label: 'Humidity (%)',
        data: data.map(d => parseFloat(d.humidity)),
        borderColor: '#4facfe', backgroundColor: 'rgba(79,172,254,0.06)',
        borderWidth: 2, pointRadius: 2, pointHoverRadius: 5,
        tension: 0.3, fill: true, yAxisID: 'y-humidity'
    }];
    if (hasCo2) datasets.push({
        label: 'CO₂ (ppm)',
        data: data.map(d => d.co2 != null ? parseFloat(d.co2) : null),
        borderColor: '#b45309', backgroundColor: 'rgba(180,83,9,0.05)',
        borderWidth: 1.5, pointRadius: 1, pointHoverRadius: 4,
        tension: 0.3, fill: false, borderDash: [4,3], yAxisID: 'y-co2', spanGaps: true
    });
    chart = new Chart(ctx, {
        type: 'line', data: { labels, datasets },
        options: { responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { position:'top', labels:{ usePointStyle:true, padding:20, color:'#4A7055', font:{size:12,family:'Poppins'} } },
                tooltip: { backgroundColor:'#FFFFFF', borderColor:'rgba(45,122,58,0.25)', borderWidth:1, titleColor:'#1A3320', bodyColor:'#4A7055', padding:12 } },
            scales: buildScales(hasCo2) }
    });
}

// Render — daily averages
function renderChartSummary(data) {
    const ctx = document.getElementById('historyChart').getContext('2d');
    if (chart) chart.destroy();
    const hasCo2 = data.some(d => d.avg_co2 != null && parseFloat(d.avg_co2) > 0);
    const datasets = [{
        label: 'Avg Temperature (°C)',
        data: data.map(d => parseFloat(d.avg_temp)),
        borderColor: '#2D7A3A', backgroundColor: 'rgba(45,122,58,0.08)',
        borderWidth: 2.5, pointRadius: 4, pointHoverRadius: 6,
        tension: 0.3, fill: true, yAxisID: 'y-temperature'
    }, {
        label: 'Avg Humidity (%)',
        data: data.map(d => parseFloat(d.avg_humidity)),
        borderColor: '#4facfe', backgroundColor: 'rgba(79,172,254,0.08)',
        borderWidth: 2.5, pointRadius: 4, pointHoverRadius: 6,
        tension: 0.3, fill: true, yAxisID: 'y-humidity'
    }];
    if (hasCo2) datasets.push({
        label: 'Avg CO₂ (ppm)',
        data: data.map(d => d.avg_co2 != null ? parseFloat(d.avg_co2) : null),
        borderColor: '#b45309', backgroundColor: 'rgba(180,83,9,0.07)',
        borderWidth: 2, pointRadius: 3, pointHoverRadius: 5,
        tension: 0.3, fill: false, borderDash: [5,3], yAxisID: 'y-co2', spanGaps: true
    });
    chart = new Chart(ctx, {
        type: 'line', data: { labels: data.map(d => d.measurement_date), datasets },
        options: { responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { position:'top', labels:{ usePointStyle:true, padding:20, color:'#4A7055', font:{size:12,family:'Poppins'} } },
                tooltip: { backgroundColor:'#FFFFFF', borderColor:'rgba(45,122,58,0.25)', borderWidth:1, titleColor:'#1A3320', bodyColor:'#4A7055', padding:12 } },
            scales: buildScales(hasCo2) }
    });
}

function buildScales(hasCo2) {
    const s = {
        'y-temperature': { type:'linear', position:'left',
            title:{ display:true, text:'Temperature (°C)', color:'#4A7055', font:{size:11} },
            ticks:{ color:'#4A7055' }, grid:{ color:'rgba(45,122,58,0.07)' } },
        'y-humidity': { type:'linear', position:'right',
            title:{ display:true, text:'Humidity (%)', color:'#4A7055', font:{size:11} },
            ticks:{ color:'#4A7055' }, grid:{ drawOnChartArea:false } },
        x: { ticks:{ color:'#4A7055', maxRotation:45, minRotation:45, maxTicksLimit:20 }, grid:{ color:'rgba(45,122,58,0.05)' } }
    };
    if (hasCo2) s['y-co2'] = { type:'linear', position:'right',
        title:{ display:true, text:'CO₂ (ppm)', color:'#b45309', font:{size:11} },
        ticks:{ color:'#b45309' }, grid:{ drawOnChartArea:false }, offset:true };
    return s;
}

// ── Tab switcher ──────────────────────────────────────────────
function switchTab(tab) {
    if (tab === 'readings') {
        $('#panelReadings').show(); $('#panelSummary').hide();
        $('#tabReadings').addClass('active'); $('#tabSummary').removeClass('active');
        loadReadingsTable();
    } else {
        $('#panelSummary').show(); $('#panelReadings').hide();
        $('#tabSummary').addClass('active'); $('#tabReadings').removeClass('active');
        loadDailyStats();
    }
}

// Individual readings table
async function loadReadingsTable() {
    const hours = parseInt($('#readingsHourFilter').val()) || 24;
    const tbody = $('#readingsTableBody');
    tbody.html('<tr><td colspan="7" class="loading">Loading...</td></tr>');
    try {
        const response = await fetch(apiUrl('/api/readings', { hours }));
        const result   = await response.json();
        if (!result.success || !result.data || !result.data.length) {
            tbody.html('<tr><td colspan="7" class="loading">No readings found for this period.</td></tr>');
            $('#readingsCount').text('0 readings'); return;
        }
        const rows = result.data;
        $('#readingsCount').text(`${rows.length} reading${rows.length !== 1 ? 's' : ''}`);
        let html = '';
        [...rows].reverse().forEach((r, i) => {
            const temp = parseFloat(r.temperature);
            let pill, pillClass;
            if      (temp < 32) { pill = 'Cold';    pillClass = 'pill-cold'; }
            else if (temp < 36) { pill = 'Optimal'; pillClass = 'pill-optimal'; }
            else if (temp < 38) { pill = 'Warm';    pillClass = 'pill-warm'; }
            else                { pill = 'Hot';     pillClass = 'pill-hot'; }
            const co2cell = r.co2 != null ? `${parseFloat(r.co2).toFixed(1)} ppm` : '—';
            html += `<tr>
                <td style="color:var(--text-dim);font-size:0.75rem;">${rows.length - i}</td>
                <td>${r.measurement_date}</td>
                <td style="font-weight:500;">${r.measurement_time}</td>
                <td><strong>${r.temperature}°C</strong></td>
                <td>${r.humidity}%</td>
                <td style="color:#b45309;font-weight:500;">${co2cell}</td>
                <td><span class="status-pill ${pillClass}">${pill}</span></td>
            </tr>`;
        });
        tbody.html(html);
    } catch(e) {
        tbody.html(`<tr><td colspan="7" class="loading">Error: ${e.message}</td></tr>`);
    }
}

// Daily summary table
async function loadDailyStats() {
    try {
        const response = await fetch(apiUrl('/api/daily_stats', { limit: 30 }));
        const result   = await response.json();
        if (result.success && result.data && result.data.length > 0) {
            renderTable(result.data);
            $('#summaryCount').text(`${result.data.length} day${result.data.length !== 1 ? 's' : ''}`);
        } else {
            $('#tableBody').html('<tr><td colspan="8" class="loading">No data available.</td></tr>');
        }
    } catch (e) {
        $('#tableBody').html('<tr><td colspan="8" class="loading">Error loading data</td></tr>');
    }
}

function renderTable(data) {
    const tbody = $('#tableBody');
    let html = '';
    data.forEach(row => {
        const avgCo2 = row.avg_co2 != null ? parseFloat(row.avg_co2).toFixed(1) + ' ppm' : '—';
        const maxCo2 = row.max_co2 != null ? parseFloat(row.max_co2).toFixed(1) + ' ppm' : '—';
        html += `<tr>
            <td>${formatDate(row.measurement_date)}</td>
            <td>${row.avg_temperature ?? '—'}°C</td>
            <td>${row.avg_humidity ?? '—'}%</td>
            <td>${row.max_temperature ?? '—'}°C</td>
            <td>${row.min_temperature ?? '—'}°C</td>
            <td style="color:#b45309;font-weight:500;">${avgCo2}</td>
            <td style="color:#b45309;font-weight:500;">${maxCo2}</td>
            <td>${row.reading_count ?? 0}</td>
        </tr>`;
    });
    tbody.html(html);
}

function refreshData() {
    loadCurrentData();
    if (chartMode === 'live') loadHistoryData(currentHours);
    else loadSummaryChart(currentDays);
    loadReadingsTable();
    loadAlerts();
    const refreshIcon = $('.btn-refresh i');
    refreshIcon.css('transform', 'rotate(360deg)');
    setTimeout(() => refreshIcon.css('transform', ''), 500);
}

function selectHive(sensorId, element) {
    $('.hive-item').removeClass('active');
    $(element).addClass('active');
    currentSensorId = sensorId;
    const hiveName = $(element).find('h4').text();
    $('#selectedHiveTitle').text(hiveName + ' Dashboard');
    $('#currentTemp, #currentHumidity, #minToday, #maxToday').text('--');
    $('#currentCo2, #minCo2, #maxCo2').text('--');
    $('#currentFood').text('--');
    $('#tempStatusBadge, #humidityStatusBadge').text('—').removeClass('cold optimal warm hot dry humid very-humid');
    $('#co2StatusBadge').text('—').removeClass('good moderate high danger');
    $('#foodStatusBadge').text('—').removeClass('full good moderate low empty');
    $('#tempTime, #humidityTime, #co2Time, #foodTime').text('--');
    $('#readingsTableBody').html('<tr><td colspan="7" class="loading">Loading data...</td></tr>');
    $('#tableBody').html('<tr><td colspan="8" class="loading">Loading data...</td></tr>');
    loadCurrentData();
    if (chartMode === 'live') loadHistoryData(currentHours);
    else loadSummaryChart(currentDays);
    loadReadingsTable();
    showToast('Switched to: ' + hiveName, 'success');
    closeSidebar();
}

// Calendar Functions
function openCalendar() {
    $('#calendarOverlay').addClass('open');
    renderCalendar();
}

function closeCalendar() {
    $('#calendarOverlay').removeClass('open');
    calSelectedDate = null;
}

function onHiveChange() {
    clearInspectionForm();
    renderCalendar();
    if (calSelectedDate) selectCalDay(calSelectedDate);
}

async function renderCalendar() {
    const monthStr = `${calYear}-${String(calMonth + 1).padStart(2, '0')}`;
    $('#calMonthLabel').text(new Date(calYear, calMonth, 1).toLocaleDateString(undefined, { month: 'long', year: 'numeric' }));
    
    const hiveId = $('#insp_hive_id').val();
    let url = BASE_URL + `/api/calendar_dots?month=${monthStr}`;
    if (hiveId) url += `&sensor_id=${hiveId}`;
    
    try {
        const response = await fetch(url);
        const result = await response.json();
        calDotDates = result.success ? result.data : [];
    } catch(e) {
        calDotDates = [];
    }
    buildCalGrid();
}

function buildCalGrid() {
    const grid = $('#calGrid');
    const today = new Date();
    const firstDay = new Date(calYear, calMonth, 1).getDay();
    const daysInMonth = new Date(calYear, calMonth + 1, 0).getDate();
    
    let html = '';
    for (let i = 0; i < firstDay; i++) {
        html += `<div class="cal-day empty"></div>`;
    }
    
    for (let d = 1; d <= daysInMonth; d++) {
        const ds = `${calYear}-${String(calMonth + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        const isToday = (today.getFullYear() === calYear && today.getMonth() === calMonth && today.getDate() === d);
        const hasNote = calDotDates.includes(ds);
        const isSelected = calSelectedDate === ds;
        
        let classes = 'cal-day';
        if (isToday) classes += ' today';
        if (hasNote) classes += ' has-note';
        if (isSelected) classes += ' selected';
        
        html += `<div class="${classes}" onclick="selectCalDay('${ds}')">${d}</div>`;
    }
    grid.html(html);
}

function calPrevMonth() {
    calMonth--;
    if (calMonth < 0) { calMonth = 11; calYear--; }
    calSelectedDate = null;
    showNotePaneEmpty();
    renderCalendar();
}

function calNextMonth() {
    calMonth++;
    if (calMonth > 11) { calMonth = 0; calYear++; }
    calSelectedDate = null;
    showNotePaneEmpty();
    renderCalendar();
}

async function selectCalDay(ds) {
    calSelectedDate = ds;
    buildCalGrid();

    $('#notePaneEmpty').hide();
    $('#noteEditor').show();

    const label = new Date(ds + 'T00:00:00').toLocaleDateString(undefined, {
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
    });
    $('#noteDateHeader').text(label);

    // FIX: build "today" from local date parts, not toISOString() (which is UTC
    // and lags behind Asia/Manila local time between 12:00–07:59 AM).
    const now = new Date();
    const today = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
    const isFuture = ds > today;

    try {
        const hiveId = $('#insp_hive_id').val();
        let url = BASE_URL + `/api/note_by_date?date=${ds}`;
        if (hiveId) url += `&sensor_id=${hiveId}`;

        const response = await fetch(url);
        const result = await response.json();

        clearInspectionForm();

        if (result.success && result.data && result.data.note_id) {
            populateInspectionForm(result.data);
        }

        setFormReadOnly(isFuture);
    } catch(e) {
        clearInspectionForm();
        setFormReadOnly(isFuture);
    }
}

function showNotePaneEmpty() {
    $('#notePaneEmpty').show();
    $('#noteEditor').hide();
}

function setFormReadOnly(readonly) {
    const editor = $('#noteEditor');
    editor.find('input, textarea, select').not('#insp_hive_id').prop('disabled', readonly);
    editor.find('.rating-pip').css('pointer-events', readonly ? 'none' : 'auto');
    editor.find('.rating-pip').css('opacity', readonly ? '0.5' : '1');
    
    if (readonly) {
        $('#btnDeleteNote').hide();
    } else {
        $('#btnDeleteNote').toggle(!!$('#noteId').val());
    }
}

function clearInspectionForm() {
    $('#noteId').val('');
    $('#insp_num_colonies').val('');
    $('#insp_queen_species').val('');
    $('#insp_queen_age').val('');
    $('#insp_comb_frames_change').val('');
    $('#insp_wax_foundation_change').val('');
    $('#insp_feeding').val('');
    $('#insp_brood_pattern_type').val('');
    $('#insp_treatment_date').val('');
    $('#insp_chemical_brand').val('');
    $('#insp_remarks').val('');
    $('#btnDeleteNote').hide();
    
    const fields = ['insp_colony_strength', 'insp_brood_pattern_close', 'insp_brood_pattern_open', 
                    'insp_honey_store', 'insp_pollen_store', 'insp_temperament'];
    fields.forEach(field => $('#' + field).val(''));
    $('.rating-pip').removeClass('active');
}

function populateInspectionForm(note) {
    $('#noteId').val(note.note_id);
    $('#insp_num_colonies').val(note.num_colonies ?? '');
    $('#insp_queen_species').val(note.queen_species ?? '');
    $('#insp_queen_age').val(note.queen_age ?? '');
    $('#insp_comb_frames_change').val(note.comb_frames_change ?? '');
    $('#insp_wax_foundation_change').val(note.wax_foundation_change ?? '');
    $('#insp_feeding').val(note.feeding !== null && note.feeding !== undefined ? String(note.feeding) : '');
    $('#insp_brood_pattern_type').val(note.brood_pattern_type ?? '');
    $('#insp_temperament').val(note.temperament ?? '');
    $('#insp_treatment_date').val(note.treatment_date ?? '');
    $('#insp_chemical_brand').val(note.chemical_brand ?? '');
    $('#insp_remarks').val(note.remarks ?? '');
    
    const ratingMap = {
        insp_colony_strength: note.colony_strength,
        insp_brood_pattern_close: note.brood_pattern_close,
        insp_brood_pattern_open: note.brood_pattern_open,
        insp_honey_store: note.honey_store,
        insp_pollen_store: note.pollen_store
    };
    
    for (const [fieldId, val] of Object.entries(ratingMap)) {
        if (val !== null && val !== undefined) setRatingBar(fieldId, parseInt(val));
    }
    $('#btnDeleteNote').show();
}

function setRatingBar(fieldId, value) {
    $('#' + fieldId).val(value);
    $(`.rating-bar[data-field="${fieldId}"] .rating-pip`).each(function() {
        const pipVal = parseInt($(this).data('val'));
        $(this).toggleClass('active', pipVal <= value);
    });
}

function initRatingBars() {
    $('.rating-bar').each(function() {
        const field = $(this).data('field');
        const max = parseInt($(this).data('max')) || 10;
        $(this).empty();
        for (let i = 1; i <= max; i++) {
            const pip = $('<div>').addClass('rating-pip').attr('data-val', i).text(i);
            pip.on('click', () => setRatingBar(field, i));
            $(this).append(pip);
        }
    });
}

function sanitizeSignedInt(el) {
    let v = el.value;

    // Allow empty input
    if (v === '') {
        return;
    }

    // Allow "-" temporarily while typing a negative number
    if (v === '-') {
        return;
    }

    // Keep only one leading minus and digits
    v = v.replace(/[^\d-]/g, '');

    // Make sure "-" can only be at the beginning
    v = v.replace(/(?!^)-/g, '');

    let num = parseInt(v, 10);

    if (isNaN(num)) {
        el.value = '';
        return;
    }

    // Limit from -10 to 10
    if (num > 10) num = 10;
    if (num < -10) num = -10;

    el.value = String(num);
}

function sanitizeSignedIntColony(el) {
    let v = el.value;
    const negative = v.trim().startsWith('-');
    let digits = v.replace(/[^0-9]/g, '');
    el.value = (negative && digits !== '') ? '-' + digits : digits;
}
// insp_num_colonies is a count — digits only, no sign at all
function sanitizeUnsignedInt(el) {
    el.value = el.value.replace(/[^0-9]/g, '');
}

async function saveCurrentNote() {
    const date = calSelectedDate;
    if (!date) { showToast('No date selected.', 'error'); return; }
    
    const payload = {
        note_id: $('#noteId').val() ? parseInt($('#noteId').val()) : null,
        note_date: date,
        sensor_id: $('#insp_hive_id').val() || null,
        num_colonies: $('#insp_num_colonies').val() || null,
        queen_species: $('#insp_queen_species').val() || null,
        queen_age: $('#insp_queen_age').val() || null,
        comb_frames_change: $('#insp_comb_frames_change').val() !== '' ? $('#insp_comb_frames_change').val() : null,
        wax_foundation_change: $('#insp_wax_foundation_change').val() !== '' ? $('#insp_wax_foundation_change').val() : null,
        feeding: $('#insp_feeding').val() !== '' ? $('#insp_feeding').val() : null,
        colony_strength: $('#insp_colony_strength').val() || null,
        brood_pattern_close: $('#insp_brood_pattern_close').val() || null,
        brood_pattern_open: $('#insp_brood_pattern_open').val() || null,
        brood_pattern_type: $('#insp_brood_pattern_type').val() || null,
        honey_store: $('#insp_honey_store').val() || null,
        pollen_store: $('#insp_pollen_store').val() || null,
        temperament: $('#insp_temperament').val() || null,
        treatment_date: $('#insp_treatment_date').val() || null,
        chemical_brand: $('#insp_chemical_brand').val() || null,
        remarks: $('#insp_remarks').val() || null
    };
    
    try {
        const response = await fetch(BASE_URL + '/api/note_save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const result = await response.json();
        
        if (!result.success) throw new Error(result.message);
        
        $('#noteId').val(result.note_id);
        $('#btnDeleteNote').show();
        
        if (!calDotDates.includes(date)) {
            calDotDates.push(date);
            buildCalGrid();
        }
        showToast('Inspection saved!', 'success');
    } catch(e) {
        showToast(`Error: ${e.message}`, 'error');
    }
}

async function deleteCurrentNote() {
    const noteId = $('#noteId').val();
    if (!noteId || !confirm('Delete this inspection record?')) return;
    
    try {
        const response = await fetch(BASE_URL + '/api/note_delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ note_id: parseInt(noteId) })
        });
        const result = await response.json();
        
        if (!result.success) throw new Error(result.message);
        
        calDotDates = calDotDates.filter(d => d !== calSelectedDate);
        buildCalGrid();
        showNotePaneEmpty();
        calSelectedDate = null;
        showToast('Inspection deleted.', 'success');
    } catch(e) {
        showToast(`Error: ${e.message}`, 'error');
    }
}

// Hive Management Functions
function openHiveManager() {
    $('#hiveManagerOverlay').addClass('open');
    loadHiveManagerList();
}

function closeHiveManager() {
    $('#hiveManagerOverlay').removeClass('open');
    resetHiveForm();
}

// FIX: Store hive data in a map keyed by id so buttons read from memory
//      instead of fragile inline onclick strings (same fix as announcements)
const hiveDataMap = {};

async function loadHiveManagerList() {
    const container = $('#hiveManagerList');
    container.html('<div class="loading">Loading...</div>');
    
    try {
        const response = await fetch(BASE_URL + '/api/hives_all');
        const result = await response.json();
        
        if (result.success && result.data) {
            if (!result.data.length) {
                container.html('<div class="loading">No hives yet. Add one above.</div>');
                return;
            }
            
            let html = '';
            result.data.forEach(h => {
                hiveDataMap[h.sensor_id] = h;
                const inactive = parseInt(h.is_active) === 0;
                const actions = inactive
                    ? `<button class="hmi-btn restore" onclick="restoreHive(${h.sensor_id})" title="Restore"><i class="fas fa-undo"></i></button>
                       <button class="hmi-btn delete" data-id="${h.sensor_id}" onclick="permanentDeleteHiveFromBtn(this)" title="Permanently Delete"><i class="fas fa-trash"></i></button>`
                    : `<button class="hmi-btn edit" data-id="${h.sensor_id}" onclick="startEditHiveFromBtn(this)" title="Edit"><i class="fas fa-pen"></i></button>
                       <button class="hmi-btn delete" data-id="${h.sensor_id}" onclick="confirmDeleteHiveFromBtn(this)" title="Deactivate"><i class="fas fa-trash"></i></button>`;
                
                html += `<div class="hive-manager-item ${inactive ? 'inactive' : ''}">
                            <div class="hmi-info">
                                <h4>${escapeHtml(h.hive_name)}${inactive ? '<span class="hmi-badge">Inactive</span>' : ''}</h4>
                                <p>${escapeHtml(h.location || '—')}</p>
                            </div>
                            <div class="hmi-actions">${actions}</div>
                        </div>`;
            });
            container.html(html);
        }
    } catch(e) {
        container.html(`<div class="loading">Error: ${e.message}</div>`);
    }
}

function startEditHiveFromBtn(btn) {
    const h = hiveDataMap[$(btn).data('id')];
    if (h) startEditHive(h.sensor_id, h.hive_name, h.location || '');
}
function confirmDeleteHiveFromBtn(btn) {
    const h = hiveDataMap[$(btn).data('id')];
    if (h) confirmDeleteHive(h.sensor_id, h.hive_name);
}
function permanentDeleteHiveFromBtn(btn) {
    const h = hiveDataMap[$(btn).data('id')];
    if (h) permanentDeleteHive(h.sensor_id, h.hive_name);
}

function startEditHive(id, name, location) {
    $('#editHiveId').val(id);
    $('#hiveNameInput').val(name);
    $('#hiveLocationInput').val(location);
    $('#hiveFormTitle').html('<i class="fas fa-save"></i> Edit Hive');
    $('#hiveFormBtnText').text('Save Changes');
    $('#hiveCancelEdit').show();
}

function resetHiveForm() {
    $('#editHiveId').val('');
    $('#hiveNameInput').val('');
    $('#hiveLocationInput').val('');
    $('#hiveFormTitle').html('<i class="fas fa-plus"></i> Add New Hive');
    $('#hiveFormBtnText').text('Add Hive');
    $('#hiveCancelEdit').hide();
}

async function submitHiveForm() {
    const id = $('#editHiveId').val();
    const hiveName = $('#hiveNameInput').val().trim();
    const location = $('#hiveLocationInput').val().trim();
    
    if (!hiveName) { showToast('Hive name is required.', 'error'); return; }
    
    try {
        let response, result;
        if (id) {
            response = await fetch(BASE_URL + '/api/hive_update', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ sensor_id: parseInt(id), hive_name: hiveName, location })
            });
        } else {
            response = await fetch(BASE_URL + '/api/hive_create', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ hive_name: hiveName, location })
            });
        }
        result = await response.json();
        if (!result.success) throw new Error(result.message);
        
        showToast(id ? 'Hive updated!' : 'Hive created!', 'success');
        resetHiveForm();
        loadHiveManagerList();
        reloadSidebarHives();
    } catch(e) { showToast(`Error: ${e.message}`, 'error'); }
}

async function confirmDeleteHive(id, name) {
    if (!confirm(`Deactivate "${name}"? Its readings will be preserved.`)) return;
    try {
        const response = await fetch(BASE_URL + '/api/hive_delete', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ sensor_id: id })
        });
        const result = await response.json();
        if (result.success) {
            showToast(`"${name}" deactivated.`, 'success');
            loadHiveManagerList();
            reloadSidebarHives();
        }
    } catch(e) { showToast(`Error: ${e.message}`, 'error'); }
}

async function restoreHive(id) {
    try {
        const response = await fetch(BASE_URL + '/api/hive_restore', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ sensor_id: id })
        });
        const result = await response.json();
        if (result.success) {
            showToast('Hive restored!', 'success');
            loadHiveManagerList();
            reloadSidebarHives();
        }
    } catch(e) { showToast(`Error: ${e.message}`, 'error'); }
}

async function permanentDeleteHive(id, name) {
    if (!confirm(`Permanently delete "${name}"? This cannot be undone.`)) return;
    try {
        const response = await fetch(BASE_URL + '/api/hive_permanent_delete', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ sensor_id: parseInt(id) })
        });
        const result = await response.json();
        if (result.success) {
            showToast(`"${name}" permanently deleted.`, 'success');
            loadHiveManagerList();
            reloadSidebarHives();
        }
    } catch(e) { showToast(`Error: ${e.message}`, 'error'); }
}

async function reloadSidebarHives() {
    try {
        const response = await fetch(BASE_URL + '/api/hives');
        const result = await response.json();
        if (result.success && result.data) {
            const hiveList = $('#hiveList');
            if (!result.data.length) {
                hiveList.html('<p style="color:var(--text-muted);font-size:0.82rem;padding:1rem;text-align:center;">No active hives.<br>Add one via the ⚙ icon.</p>');
                currentSensorId = null;
                updateCalendarHiveSelect(result.data);
                return;
            }
            let html = '';
            result.data.forEach((h, i) => {
                // FIX: Keep the previously selected hive active if it still exists
                const isActive = currentSensorId ? h.sensor_id == currentSensorId : i === 0;
                if (isActive) currentSensorId = h.sensor_id;
                html += `<div class="hive-item ${isActive ? 'active' : ''}" data-id="${h.sensor_id}" onclick="selectHive(${h.sensor_id}, this)">
                            <div class="hive-icon"><i class="fas fa-bezier-curve"></i></div>
                            <div class="hive-info">
                                <h4>${escapeHtml(h.hive_name)}</h4>
                                <p>${escapeHtml(h.location || '')}</p>
                            </div>
                        </div>`;
            });
            hiveList.html(html);
            updateCalendarHiveSelect(result.data);
        }
    } catch(e) { console.error('reloadSidebarHives error:', e); }
}

// FIX: Keep the Inspection Calendar's "Select Hive" dropdown in sync with active hives
function updateCalendarHiveSelect(hives) {
    const select = $('#insp_hive_id');
    if (!select.length) return;
    const currentVal = select.val();
    let html = '<option value="">All Hives</option>';
    (hives || []).forEach(h => {
        html += `<option value="${h.sensor_id}">${escapeHtml(h.hive_name)}</option>`;
    });
    select.html(html);
    if (currentVal && select.find(`option[value="${currentVal}"]`).length) {
        select.val(currentVal);
    } else {
        select.val('');
    }
}

// Toast notification
let toastTimer = null;
function showToast(msg, type = 'success') {
    const toast = $('#toast');
    toast.text(msg);
    toast.removeClass('success error info').addClass(`${type} show`);
    if (toastTimer) clearTimeout(toastTimer);
    const duration = type === 'info' ? 6000 : 3000;
    toastTimer = setTimeout(() => toast.removeClass('show'), duration);
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

window.addEventListener('beforeunload', () => { if (refreshTimer) clearInterval(refreshTimer); });

// ── User Menu ─────────────────────────────────────────────────────
function toggleUserMenu() {
    $('#userMenuWrap').toggleClass('open');
}
$(document).on('click', function(e) {
    if (!$(e.target).closest('#userMenuWrap').length) {
        $('#userMenuWrap').removeClass('open');
    }
});

// ── User Manager ──────────────────────────────────────────────────
function openUserManager() {
    $('#userMenuWrap').removeClass('open');
    $('#userManagerOverlay').addClass('open');
    loadUserManagerList();
}
function closeUserManager() {
    $('#userManagerOverlay').removeClass('open');
    resetUserForm();
}

async function loadUserManagerList() {
    const container = $('#userManagerList');
    container.html('<div class="loading">Loading...</div>');
    try {
        const res = await fetch(BASE_URL + '/api/users');
        const result = await res.json();
        if (!result.success || !result.data || !result.data.length) {
            container.html('<div class="loading">No users found.</div>');
            return;
        }
        let html = '';
        result.data.forEach(u => {
            const inactive = parseInt(u.is_active) === 0;
            const roleBadge = `<span class="umi-badge-role ${u.role === 'admin' ? 'admin' : ''}">${u.role}</span>`;
            const inactiveBadge = inactive ? '<span class="umi-badge-inactive">Inactive</span>' : '';
            const lastLogin = u.last_login_at ? new Date(u.last_login_at).toLocaleDateString() : 'Never';
            // FIX: Store edit data in data-attributes to avoid inline string escaping issues
            html += `<div class="user-manager-item ${inactive ? 'inactive' : ''}">
                <div class="umi-info">
                    <h4>${escapeHtml(u.full_name || u.username)}${roleBadge}${inactiveBadge}</h4>
                    <p>${escapeHtml(u.email)} &mdash; Last login: ${lastLogin}</p>
                </div>
                <div class="umi-actions">
                    <button class="hmi-btn edit" title="Edit"
                        data-uid="${u.user_id}"
                        data-username="${escapeHtml(u.username)}"
                        data-email="${escapeHtml(u.email)}"
                        data-fullname="${escapeHtml(u.full_name || '')}"
                        data-role="${u.role}"
                        onclick="startEditUserFromBtn(this)">
                        <i class="fas fa-pen"></i>
                    </button>
                    <button class="hmi-btn edit" title="Reset Password"
                        data-uid="${u.user_id}"
                        data-username="${escapeHtml(u.username)}"
                        onclick="openResetPasswordFromBtn(this)">
                        <i class="fas fa-key"></i>
                    </button>
                    <button class="hmi-btn ${inactive ? 'restore' : 'delete'}" title="${inactive ? 'Enable' : 'Disable'}"
                        onclick="toggleUserActive(${u.user_id})">
                        <i class="fas fa-toggle-${inactive ? 'on' : 'off'}"></i>
                    </button>
                    <button class="hmi-btn delete" title="Delete"
                        data-uid="${u.user_id}"
                        data-username="${escapeHtml(u.username)}"
                        onclick="confirmDeleteUserFromBtn(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>`;
        });
        container.html(html);
    } catch(e) {
        container.html(`<div class="loading">Error: ${e.message}</div>`);
    }
}

// FIX: Read from data-attributes instead of inline string params to avoid quote-breaking
function startEditUserFromBtn(btn) {
    const $btn = $(btn);
    startEditUser(
        $btn.data('uid'),
        $btn.data('username'),
        $btn.data('email'),
        $btn.data('fullname'),
        $btn.data('role')
    );
}
function openResetPasswordFromBtn(btn) {
    const $btn = $(btn);
    openResetPassword($btn.data('uid'), $btn.data('username'));
}
function confirmDeleteUserFromBtn(btn) {
    const $btn = $(btn);
    confirmDeleteUser($btn.data('uid'), $btn.data('username'));
}

function startEditUser(id, username, email, fullName, role) {
    $('#editUserId').val(id);
    $('#userUsernameInput').val(username);
    $('#userEmailInput').val(email);
    $('#userFullNameInput').val(fullName);
    $('#userRoleInput').val(role);
    $('#passwordFieldWrap').hide();
    $('#userFormTitle').html('<i class="fas fa-save"></i> Edit User');
    $('#userFormBtnText').text('Save Changes');
    $('#userCancelEdit').show();
}
function resetUserForm() {
    $('#editUserId').val('');
    $('#userUsernameInput,#userEmailInput,#userFullNameInput,#userPasswordInput').val('');
    $('#userRoleInput').val('viewer');
    $('#passwordFieldWrap').show();
    $('#userFormTitle').html('<i class="fas fa-plus"></i> Add New User');
    $('#userFormBtnText').text('Add User');
    $('#userCancelEdit').hide();
}
async function submitUserForm() {
    const id       = $('#editUserId').val();
    const username = $('#userUsernameInput').val().trim();
    const email    = $('#userEmailInput').val().trim();
    const fullName = $('#userFullNameInput').val().trim();
    const role     = $('#userRoleInput').val();
    const password = $('#userPasswordInput').val();
    if (!username || !email) { showToast('Username and email are required.', 'error'); return; }
    try {
        let res;
        if (id) {
            res = await fetch(BASE_URL + '/api/user_update', {
                method:'POST', headers:{'Content-Type':'application/json'},
                body: JSON.stringify({ user_id:parseInt(id), username, email, full_name:fullName, role })
            });
        } else {
            if (!password) { showToast('Password is required.', 'error'); return; }
            res = await fetch(BASE_URL + '/api/user_create', {
                method:'POST', headers:{'Content-Type':'application/json'},
                body: JSON.stringify({ username, email, password, full_name:fullName, role })
            });
        }
        const result = await res.json();
        if (!result.success) throw new Error(result.message);
        showToast(id ? 'User updated!' : 'User created!', 'success');
        resetUserForm();
        loadUserManagerList();
    } catch(e) { showToast('Error: ' + e.message, 'error'); }
}
async function toggleUserActive(id) {
    try {
        const res = await fetch(BASE_URL + '/api/user_toggle_active', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ user_id: id })
        });
        const result = await res.json();
        if (!result.success) throw new Error(result.message);
        showToast('User status updated.', 'success');
        loadUserManagerList();
    } catch(e) { showToast('Error: ' + e.message, 'error'); }
}
async function confirmDeleteUser(id, username) {
    if (!confirm(`Permanently delete user "${username}"? This cannot be undone.`)) return;
    try {
        const res = await fetch(BASE_URL + '/api/user_delete', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ user_id: id })
        });
        const result = await res.json();
        if (!result.success) throw new Error(result.message);
        showToast('User deleted.', 'success');
        loadUserManagerList();
    } catch(e) { showToast('Error: ' + e.message, 'error'); }
}

// ── Admin Reset Password ──────────────────────────────────────────
function openResetPassword(id, username) {
    $('#resetPwUserId').val(id);
    $('#resetPwUsername').text(username);
    $('#resetPwNew').val('');
    $('#resetPwOverlay').addClass('open');
}
function closeResetPassword() { $('#resetPwOverlay').removeClass('open'); }
async function submitResetPassword() {
    const id  = parseInt($('#resetPwUserId').val());
    const pwd = $('#resetPwNew').val();
    if (pwd.length < 8) { showToast('Password must be at least 8 characters.', 'error'); return; }
    try {
        const res = await fetch(BASE_URL + '/api/user_reset_password', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ user_id: id, password: pwd })
        });
        const result = await res.json();
        if (!result.success) throw new Error(result.message);
        showToast('Password reset!', 'success');
        closeResetPassword();
    } catch(e) { showToast('Error: ' + e.message, 'error'); }
}

// ── Change Own Password ───────────────────────────────────────────
function openChangePassword() {
    $('#userMenuWrap').removeClass('open');
    $('#cpCurrentPw,#cpNewPw,#cpConfirmPw').val('');
    $('#changePwOverlay').addClass('open');
}
function closeChangePassword() { $('#changePwOverlay').removeClass('open'); }
async function submitChangePassword() {
    const current = $('#cpCurrentPw').val();
    const newPw   = $('#cpNewPw').val();
    const confirm = $('#cpConfirmPw').val();
    if (!current)          { showToast('Enter your current password.', 'error'); return; }
    if (newPw.length < 8)  { showToast('New password must be at least 8 characters.', 'error'); return; }
    if (newPw !== confirm)  { showToast('Passwords do not match.', 'error'); return; }
    try {
        const res = await fetch(BASE_URL + '/api/user_change_password', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ current_password: current, new_password: newPw })
        });
        const result = await res.json();
        if (!result.success) throw new Error(result.message);
        showToast('Password changed successfully!', 'success');
        closeChangePassword();
    } catch(e) { showToast('Error: ' + e.message, 'error'); }
}

// ══════════════════════════════════════════════════════════════
//  ANNOUNCEMENT MANAGER
// ══════════════════════════════════════════════════════════════

const ANN_TYPE_LABELS = {
    info:    { icon: 'fa-info-circle',          label: 'Info',    color: '#2980b9' },
    success: { icon: 'fa-check-circle',         label: 'Update',  color: '#27AE60' },
    warning: { icon: 'fa-exclamation-triangle', label: 'Warning', color: '#C9A30E' },
    alert:   { icon: 'fa-bell',                 label: 'Alert',   color: '#E05252' },
};

// FIX: Store announcement data in a JS map keyed by id
//      so Edit buttons read from memory instead of broken inline strings
const annDataMap = {};

function openAnnouncementManager() {
    $('#annManagerOverlay').addClass('open');
    loadAnnList();
}
function closeAnnouncementManager() {
    $('#annManagerOverlay').removeClass('open');
    resetAnnForm();
}

async function loadAnnList() {
    const container = $('#annManagerList');
    container.html('<div class="loading">Loading...</div>');
    try {
        const res    = await fetch(BASE_URL + '/api/announcements');
        const result = await res.json();
        if (!result.success || !result.data || !result.data.length) {
            container.html('<div class="loading">No announcements yet.</div>');
            return;
        }
        let html = '';
        result.data.forEach(a => {
            // FIX: Store full announcement object so startEditAnn can read it safely
            annDataMap[a.id] = a;

            const t    = ANN_TYPE_LABELS[a.type] || ANN_TYPE_LABELS.info;
            const date = new Date(a.created_at).toLocaleDateString('en-PH', { year:'numeric', month:'short', day:'numeric' });
            const inactive = parseInt(a.is_active) === 0;

            html += `<div class="hive-manager-item ${inactive ? 'inactive' : ''}" style="flex-direction:column;align-items:flex-start;gap:10px;">
                <div style="display:flex;align-items:center;justify-content:space-between;width:100%;gap:8px;">
                    <div style="display:flex;align-items:center;gap:8px;min-width:0;">
                        <i class="fas ${t.icon}" style="color:${t.color};flex-shrink:0;"></i>
                        <span style="font-size:0.88rem;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escapeHtml(a.title)}</span>
                        ${inactive ? '<span class="hmi-badge" style="flex-shrink:0;">Hidden</span>' : ''}
                    </div>
                    <div class="hmi-actions" style="flex-shrink:0;">
                        <button class="hmi-btn edit" title="Edit" onclick="startEditAnn(${a.id})">
                            <i class="fas fa-pen"></i>
                        </button>
                        <button class="hmi-btn ${inactive ? 'restore' : 'delete'}" title="${inactive ? 'Publish' : 'Hide'}" onclick="toggleAnn(${a.id})">
                            <i class="fas fa-${inactive ? 'eye' : 'eye-slash'}"></i>
                        </button>
                        <button class="hmi-btn delete" title="Delete" onclick="deleteAnn(${a.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div style="font-size:0.78rem;color:var(--text-muted);padding-left:2px;">
                    <i class="fas fa-user" style="font-size:0.68rem;margin-right:4px;"></i>${escapeHtml(a.author || 'Admin')}
                    &nbsp;&bull;&nbsp;
                    <i class="fas fa-calendar" style="font-size:0.68rem;margin-right:4px;"></i>${date}
                </div>
            </div>`;
        });
        container.html(html);
    } catch(e) {
        container.html(`<div class="loading">Error: ${e.message}</div>`);
    }
}

// FIX: startEditAnn now takes only the id and reads from annDataMap — no fragile inline strings
function startEditAnn(id) {
    const a = annDataMap[id];
    if (!a) return;

    $('#annEditId').val(a.id);
    $('#annTitle').val(a.title);
    $('#annBody').val(a.body);         // jQuery .val() handles newlines correctly
    $('#annType').val(a.type);
    $('#annIsActive').val(String(a.is_active));
    $('#annActiveWrap').show();
    $('#annFormTitle').html('<i class="fas fa-save"></i> Edit Announcement');
    $('#annFormBtnText').text('Save Changes');
    $('#annCancelEdit').show();
    $('#annManagerOverlay .modal-body')[0].scrollTo({ top: 0, behavior: 'smooth' });
}

function resetAnnForm() {
    $('#annEditId').val('');
    $('#annTitle, #annBody').val('');
    $('#annType').val('info');
    $('#annIsActive').val('1');
    $('#annActiveWrap').hide();
    $('#annFormTitle').html('<i class="fas fa-plus"></i> Post New Announcement');
    $('#annFormBtnText').text('Post Announcement');
    $('#annCancelEdit').hide();
}

async function submitAnnForm() {
    const id       = $('#annEditId').val();
    const title    = $('#annTitle').val().trim();
    const body     = $('#annBody').val().trim();
    const type     = $('#annType').val();
    const isActive = parseInt($('#annIsActive').val());

    if (!title || !body) { showToast('Title and message are required.', 'error'); return; }

    try {
        let res;
        if (id) {
            res = await fetch(BASE_URL + '/api/announcement_update', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: parseInt(id), title, body, type, is_active: isActive })
            });
        } else {
            res = await fetch(BASE_URL + '/api/announcement_create', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ title, body, type })
            });
        }
        const result = await res.json();
        if (!result.success) throw new Error(result.message || 'Request failed');
        showToast(id ? 'Announcement updated!' : 'Announcement posted!', 'success');
        resetAnnForm();
        loadAnnList();
    } catch(e) { showToast('Error: ' + e.message, 'error'); }
}

async function toggleAnn(id) {
    try {
        const res = await fetch(BASE_URL + '/api/announcement_toggle', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const result = await res.json();
        if (!result.success) throw new Error(result.message || 'Failed');
        showToast('Visibility updated.', 'success');
        loadAnnList();
    } catch(e) { showToast('Error: ' + e.message, 'error'); }
}

// FIX: deleteAnn now reads title from annDataMap instead of inline param
async function deleteAnn(id) {
    const a = annDataMap[id];
    const title = a ? a.title : 'this announcement';
    if (!confirm(`Delete announcement "${title}"? This cannot be undone.`)) return;
    try {
        const res = await fetch(BASE_URL + '/api/announcement_delete', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const result = await res.json();
        if (!result.success) throw new Error(result.message || 'Failed');
        showToast('Announcement deleted.', 'success');
        loadAnnList();
    } catch(e) { showToast('Error: ' + e.message, 'error'); }
}

// ── Alerts ────────────────────────────────────────────────────────
let notifiedAlertIds = new Set();

async function loadAlerts() {
    if (!$('#alertPanelWrap').length) return;
    try {
        const res = await fetch(BASE_URL + '/api/alerts');
        const result = await res.json();
        if (!result.success) return;
        renderAlertPanel(result.data);
        result.data
            .filter(a => !notifiedAlertIds.has(a.alert_id))
            .forEach(a => { notifiedAlertIds.add(a.alert_id); notifyAlert(a); });
    } catch(e) { console.error('loadAlerts error:', e); }
}

function notifyAlert(a) {
    const label = a.hive_name || 'Unassigned Sensor';
    const msg   = `[${label}] ${a.message}`;
    showToast(msg, a.severity === 'critical' ? 'error' : 'info');
}

function renderAlertPanel(alerts) {
    const badge = $('#alertBadge');
    const list  = $('#alertList');
    const bellBtn = $('.btn-icon-bell');

    if (!alerts.length) {
        badge.hide();
        bellBtn.removeClass('has-critical has-warning');
        list.html('<div class="alert-empty">No active alerts. All hives look good.</div>');
        return;
    }

    badge.text(alerts.length).show();
    const hasCritical = alerts.some(a => a.severity === 'critical');
    bellBtn.toggleClass('has-critical', hasCritical);
    bellBtn.toggleClass('has-warning', !hasCritical);

    let html = '';
    alerts.forEach(a => {
        const icon = a.alert_type === 'temperature' ? 'fa-thermometer-half'
                   : a.alert_type === 'humidity'    ? 'fa-tint' : 'fa-cloud';
        html += `<div class="alert-item ${a.severity}">
            <div class="alert-item-icon"><i class="fas ${icon}"></i></div>
            <div class="alert-item-body">
                <div class="alert-item-title">${escapeHtml(a.hive_name || 'Unassigned Sensor')}</div>
                <div class="alert-item-msg">${escapeHtml(a.message)}</div>
                <div class="alert-item-time">${formatDate(a.created_at)}</div>
            </div>
            <button class="alert-ack-btn" onclick="acknowledgeAlert(${a.alert_id})" title="Acknowledge">
                <i class="fas fa-check"></i>
            </button>
        </div>`;
    });
    list.html(html);
}

async function acknowledgeAlert(id) {
    try {
        const res = await fetch(BASE_URL + '/api/alert_acknowledge', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ alert_id: id })
        });
        const result = await res.json();
        if (!result.success) throw new Error(result.message);
        showToast('Alert acknowledged.', 'success');
        loadAlerts();
    } catch(e) { showToast('Error: ' + e.message, 'error'); }
}

function toggleAlertPanel() {
    $('#alertPanelWrap').toggleClass('open');
}
$(document).on('click', function(e) {
    if (!$(e.target).closest('#alertPanelWrap').length) {
        $('#alertPanelWrap').removeClass('open');
    }
});

// ── Alert History ────────────────────────────────────────────────
function openAlertHistory() {
    $('#alertPanelWrap').removeClass('open');
    $('#alertHistoryOverlay').addClass('open');
    loadAlertHistory();
}
function closeAlertHistory() {
    $('#alertHistoryOverlay').removeClass('open');
}

async function loadAlertHistory() {
    const container = $('#alertHistoryList');
    container.html('<div class="loading">Loading...</div>');
    const hiveId = $('#alertHistoryHiveFilter').val();
    let url = BASE_URL + '/api/alert_history?limit=100';
    if (hiveId) url += `&sensor_id=${hiveId}`;

    try {
        const res = await fetch(url);
        const result = await res.json();
        if (!result.success || !result.data || !result.data.length) {
            container.html('<div class="loading">No alert history found.</div>');
            $('#alertHistoryCount').text('0 alerts');
            return;
        }
        $('#alertHistoryCount').text(`${result.data.length} alert${result.data.length !== 1 ? 's' : ''}`);
        let html = '';
        result.data.forEach(a => {
            const icon = a.alert_type === 'temperature' ? 'fa-thermometer-half'
                       : a.alert_type === 'humidity'    ? 'fa-tint' : 'fa-cloud';
            const statusBadge = a.status === 'active'
                ? '<span class="ahi-status active">Active</span>'
                : '<span class="ahi-status resolved">Resolved</span>';
            const resolvedInfo = a.resolved_at
                ? `<span class="ahi-resolved-time">Resolved: ${formatDate(a.resolved_at)}</span>` : '';
            html += `<div class="alert-history-item ${a.severity}">
                <div class="alert-item-icon"><i class="fas ${icon}"></i></div>
                <div class="alert-item-body">
                    <div class="alert-item-title">
                        ${escapeHtml(a.hive_name || 'Unassigned Sensor')} ${statusBadge}
                    </div>
                    <div class="alert-item-msg">${escapeHtml(a.message)}</div>
                    <div class="alert-item-time">${formatDate(a.created_at)} ${resolvedInfo}</div>
                </div>
            </div>`;
        });
        container.html(html);
    } catch(e) {
        container.html(`<div class="loading">Error: ${e.message}</div>`);
    }
}

// ── Compare Notes ─────────────────────────────────────────────
const COMPARE_FIELDS = [
    { key: 'num_colonies', label: 'Colony ID' },
    { key: 'queen_species', label: 'Queen Species/Breed' },
    { key: 'queen_age', label: 'Queen Age' },
    { key: 'comb_frames_change', label: 'Comb Frames (+/-)' },
    { key: 'wax_foundation_change', label: 'Wax Foundation (+/-)' },
    { key: 'feeding', label: 'Feeding', format: v => (v === '1' || v === 1) ? 'Yes' : ((v === '0' || v === 0) ? 'No' : null) },
    { key: 'colony_strength', label: 'Colony Strength' },
    { key: 'brood_pattern_open', label: 'Open Brood' },
    { key: 'brood_pattern_close', label: 'Close Brood' },
    { key: 'brood_pattern_type', label: 'Brood Pattern Type' },
    { key: 'honey_store', label: 'Honey Store' },
    { key: 'pollen_store', label: 'Pollen Store' },
    { key: 'temperament', label: 'Temperament' },
    { key: 'treatment_date', label: 'Treatment Date' },
    { key: 'chemical_brand', label: 'Chemical / Brand' },
    { key: 'remarks', label: 'Remarks' },
];

let compareNoteA = null;
let compareNoteB = null;

function openCompareNotes() {
    $('#compareOverlay').addClass('open');
    loadCompareDateOptions();
}

function closeCompareNotes() {
    $('#compareOverlay').removeClass('open');
    compareNoteA = null;
    compareNoteB = null;
    $('#compareTableWrap').hide();
    $('#compareEmpty').show();
}

async function loadCompareDateOptions() {
    const hiveId = $('#insp_hive_id').val();
    if (!hiveId) {
        $('#compareDateA, #compareDateB').html('<option value="">Select a hive first</option>');
        $('#compareEmpty').text('Pick a specific hive in the calendar (not "All Hives") before comparing.');
        return;
    }
    try {
        const res = await fetch(`${BASE_URL}/api/hive_note_dates?sensor_id=${hiveId}`);
        const result = await res.json();
        const dates = (result.success && result.data) ? result.data : [];
        let opts = '<option value="">Select date...</option>';
        dates.forEach(d => { opts += `<option value="${d.note_id}">${formatDate(d.note_date)}</option>`; });
        $('#compareDateA, #compareDateB').html(opts);
        $('#compareEmpty').text(dates.length ? 'Select two inspection dates above to compare.' : 'No inspection notes exist yet for this hive.');
    } catch(e) {
        $('#compareDateA, #compareDateB').html('<option value="">Error loading dates</option>');
    }
}

async function loadCompareSide(side) {
    const noteId = $(`#compareDate${side}`).val();
    if (!noteId) {
        if (side === 'A') compareNoteA = null; else compareNoteB = null;
        renderCompareTable();
        return;
    }
    try {
        const res = await fetch(`${BASE_URL}/api/note_by_id?note_id=${noteId}`);
        const result = await res.json();
        const note = result.success ? result.data : null;
        if (side === 'A') compareNoteA = note; else compareNoteB = note;
    } catch(e) {
        if (side === 'A') compareNoteA = null; else compareNoteB = null;
    }
    renderCompareTable();
}

function renderCompareTable() {
    if (!compareNoteA && !compareNoteB) {
        $('#compareTableWrap').hide();
        $('#compareEmpty').show();
        return;
    }
    $('#compareEmpty').hide();
    $('#compareTableWrap').show();
    $('#compareHeaderA').text(compareNoteA ? formatDate(compareNoteA.note_date) : '—');
    $('#compareHeaderB').text(compareNoteB ? formatDate(compareNoteB.note_date) : '—');

    let html = '';
    COMPARE_FIELDS.forEach(f => {
        let valA = compareNoteA ? compareNoteA[f.key] : null;
        let valB = compareNoteB ? compareNoteB[f.key] : null;
        if (f.format) { valA = f.format(valA); valB = f.format(valB); }
        const displayA = (valA === null || valA === undefined || valA === '') ? '—' : escapeHtml(String(valA));
        const displayB = (valB === null || valB === undefined || valB === '') ? '—' : escapeHtml(String(valB));
        const isDiff = compareNoteA && compareNoteB && displayA !== displayB;
        const clsA = displayA === '—' ? 'empty-val' : (isDiff ? 'diff' : '');
        const clsB = displayB === '—' ? 'empty-val' : (isDiff ? 'diff' : '');
        const labelA = compareNoteA ? formatDate(compareNoteA.note_date) : 'Date A';
        const labelB = compareNoteB ? formatDate(compareNoteB.note_date) : 'Date B';
        html += `<tr>
            <td class="field-label" data-label="Field">${f.label}</td>
            <td class="${clsA}" data-label="${labelA}">${displayA}</td>
            <td class="${clsB}" data-label="${labelB}">${displayB}</td>
        </tr>`;
    });
    $('#compareTableBody').html(html);
}

    function exportNotesCsv() {
        const hiveId = $('#insp_hive_id').val();
        if (!hiveId) {
            showToast('Select a specific hive first (not "All Hives").', 'error');
            return;
        }
        window.location.href = `${BASE_URL}/api/note_export?sensor_id=${hiveId}`;
    }