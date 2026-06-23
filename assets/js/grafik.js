document.addEventListener('DOMContentLoaded', function () {
    var canvas = document.getElementById('graphCanvas');
    if (!canvas) return;

    var ctx = canvas.getContext('2d');
    var W = canvas.width;
    var H = canvas.height;

    var MARGIN = { top: 30, right: 30, bottom: 50, left: 65 };
    var plotW = W - MARGIN.left - MARGIN.right;
    var plotH = H - MARGIN.top - MARGIN.bottom;

    var data = graphData;
    var maxX = data.maxX1 || 100;
    var maxY = data.maxX2 || 100;

    var gridSteps = 5;
    var scaleX = plotW / maxX;
    var scaleY = plotH / maxY;

    var colors = ['#4f46e5', '#dc2626', '#059669', '#d97706', '#7c3aed', '#0891b2', '#be185d', '#65a30d'];

    function toCanvas(x, y) {
        return [MARGIN.left + x * scaleX, MARGIN.top + plotH - y * scaleY];
    }

    function drawGrid() {
        ctx.save();

        for (var i = 0; i <= gridSteps; i++) {
            var valX = (maxX / gridSteps) * i;
            var valY = (maxY / gridSteps) * i;
            var px = MARGIN.left + valX * scaleX;
            var py = MARGIN.top + plotH - valY * scaleY;

            ctx.strokeStyle = '#e2e8f0';
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(px, MARGIN.top);
            ctx.lineTo(px, MARGIN.top + plotH);
            ctx.stroke();

            ctx.beginPath();
            ctx.moveTo(MARGIN.left, py);
            ctx.lineTo(MARGIN.left + plotW, py);
            ctx.stroke();

            ctx.fillStyle = '#64748b';
            ctx.font = '11px Inter, sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'top';
            ctx.fillText(formatLabel(valX), px, MARGIN.top + plotH + 6);

            ctx.textAlign = 'right';
            ctx.textBaseline = 'middle';
            ctx.fillText(formatLabel(valY), MARGIN.left - 8, py);
        }

        ctx.restore();
    }

    function drawAxes() {
        ctx.save();

        ctx.strokeStyle = '#1e293b';
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.moveTo(MARGIN.left, MARGIN.top);
        ctx.lineTo(MARGIN.left, MARGIN.top + plotH);
        ctx.lineTo(MARGIN.left + plotW, MARGIN.top + plotH);
        ctx.stroke();

        ctx.fillStyle = '#1e293b';
        ctx.font = 'bold 13px Inter, sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'top';
        ctx.fillText('X₁', MARGIN.left + plotW + 15, MARGIN.top + plotH - 2);

        ctx.textAlign = 'center';
        ctx.textBaseline = 'bottom';
        ctx.fillText('X₂', MARGIN.left - 2, MARGIN.top - 10);

        ctx.font = '11px Inter, sans-serif';
        ctx.fillStyle = '#64748b';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'top';
        ctx.fillText('0', MARGIN.left - 12, MARGIN.top + plotH + 6);

        ctx.restore();
    }

    function drawConstraints() {
        var cons = data.constraints;

        for (var i = 0; i < cons.length; i++) {
            var a1 = cons[i].a1, a2 = cons[i].a2, rhs = cons[i].rhs;
            var color = colors[i % colors.length];
            ctx.save();

            var p1 = null, p2 = null;

            if (Math.abs(a1) > 1e-10 && Math.abs(a2) > 1e-10) {
                p1 = { x: 0, y: rhs / a2 };
                p2 = { x: rhs / a1, y: 0 };
            } else if (Math.abs(a1) > 1e-10) {
                var xVal = rhs / a1;
                p1 = { x: xVal, y: 0 };
                p2 = { x: xVal, y: maxX2 };
            } else if (Math.abs(a2) > 1e-10) {
                var yVal = rhs / a2;
                p1 = { x: 0, y: yVal };
                p2 = { x: maxX, y: yVal };
            }

            if (p1 && p2) {
                var c1 = toCanvas(p1.x, p1.y);
                var c2 = toCanvas(p2.x, p2.y);

                var cy1 = Math.max(MARGIN.top, Math.min(MARGIN.top + plotH, c1[1]));
                var cy2 = Math.max(MARGIN.top, Math.min(MARGIN.top + plotH, c2[1]));

                ctx.strokeStyle = color;
                ctx.lineWidth = 2.5;
                ctx.setLineDash([6, 4]);
                ctx.beginPath();
                ctx.moveTo(c1[0], cy1);
                ctx.lineTo(c2[0], cy2);
                ctx.stroke();
                ctx.setLineDash([]);

                var midX = (p1.x + p2.x) / 2;
                var midY = (p1.y + p2.y) / 2;
                if (midX >= 0 && midX <= maxX && midY >= 0 && midY <= maxY) {
                    var cm = toCanvas(midX, midY);
                    ctx.fillStyle = color;
                    ctx.font = 'bold 11px Inter, sans-serif';
                    ctx.textAlign = 'left';
                    ctx.textBaseline = 'bottom';
                    var label = (i + 1) + '. ' + formatLabel(a1) + 'X₁ + ' + formatLabel(a2) + 'X₂ ≤ ' + formatLabel(rhs);
                    ctx.fillText(label, cm[0] + 5, cm[1] - 3);
                }
            }

            ctx.restore();
        }
    }

    function drawFeasibleRegion() {
        var points = data.cornerPoints;
        if (points.length < 3) return;

        var poly = points.slice().sort(function (a, b) {
            var cx = points.reduce(function (s, p) { return s + p.x1; }, 0) / points.length;
            var cy = points.reduce(function (s, p) { return s + p.x2; }, 0) / points.length;
            var angleA = Math.atan2(a.x2 - cy, a.x1 - cx);
            var angleB = Math.atan2(b.x2 - cy, b.x1 - cx);
            return angleA - angleB;
        });

        ctx.save();
        ctx.beginPath();
        var first = toCanvas(poly[0].x1, poly[0].x2);
        ctx.moveTo(first[0], first[1]);
        for (var i = 1; i < poly.length; i++) {
            var pt = toCanvas(poly[i].x1, poly[i].x2);
            ctx.lineTo(pt[0], pt[1]);
        }
        ctx.closePath();

        ctx.fillStyle = 'rgba(79, 70, 229, 0.08)';
        ctx.fill();

        ctx.strokeStyle = 'rgba(79, 70, 229, 0.25)';
        ctx.lineWidth = 2;
        ctx.stroke();
        ctx.restore();
    }

    function drawCornerPoints() {
        var points = data.cornerPoints;

        for (var i = 0; i < points.length; i++) {
            var p = points[i];
            var cp = toCanvas(p.x1, p.x2);
            var isOpt = p.isOptimal;
            var label = String.fromCharCode(65 + i);

            ctx.save();

            if (isOpt) {
                ctx.shadowColor = '#10b981';
                ctx.shadowBlur = 20;
                ctx.fillStyle = '#10b981';
                ctx.beginPath();
                ctx.arc(cp[0], cp[1], 9, 0, 2 * Math.PI);
                ctx.fill();
                ctx.shadowBlur = 0;
                ctx.fillStyle = '#fff';
                ctx.beginPath();
                ctx.arc(cp[0], cp[1], 6, 0, 2 * Math.PI);
                ctx.fill();

                ctx.fillStyle = '#059669';
                ctx.font = 'bold 13px Inter, sans-serif';
                ctx.textAlign = 'left';
                ctx.textBaseline = 'bottom';
                ctx.fillText(label + ' (' + formatLabel(p.x1) + ', ' + formatLabel(p.x2) + ')', cp[0] + 12, cp[1] - 5);

                ctx.font = 'bold 11px Inter, sans-serif';
                ctx.fillStyle = '#059669';
                ctx.textBaseline = 'top';
                ctx.fillText('Z = ' + formatLabel(p.z), cp[0] + 12, cp[1] + 5);
            } else {
                ctx.fillStyle = '#4f46e5';
                ctx.beginPath();
                ctx.arc(cp[0], cp[1], 5, 0, 2 * Math.PI);
                ctx.fill();

                ctx.fillStyle = '#334155';
                ctx.font = '11px Inter, sans-serif';
                ctx.textAlign = 'left';
                ctx.textBaseline = 'bottom';
                ctx.fillText(label + ' (' + formatLabel(p.x1) + ', ' + formatLabel(p.x2) + ')', cp[0] + 8, cp[1] - 3);
            }

            ctx.restore();
        }
    }

    function drawLegend() {
        var cons = data.constraints;
        var legendX = MARGIN.left + 10;
        var legendY = MARGIN.top + 10;

        ctx.save();
        ctx.fillStyle = 'rgba(255,255,255,0.92)';
        ctx.shadowColor = 'rgba(0,0,0,0.08)';
        ctx.shadowBlur = 8;
        ctx.beginPath();
        ctx.roundRect(legendX, legendY, 200, cons.length * 22 + 28, 6);
        ctx.fill();
        ctx.shadowBlur = 0;

        ctx.fillStyle = '#1e293b';
        ctx.font = 'bold 11px Inter, sans-serif';
        ctx.textAlign = 'left';
        ctx.textBaseline = 'top';
        ctx.fillText('Kendala', legendX + 12, legendY + 8);

        for (var i = 0; i < cons.length; i++) {
            var cy = legendY + 30 + i * 22;
            ctx.fillStyle = colors[i % colors.length];
            ctx.fillRect(legendX + 12, cy + 3, 14, 3);

            ctx.fillStyle = '#334155';
            ctx.font = '11px Inter, sans-serif';
            ctx.fillText((i + 1) + '. ' + formatLabel(cons[i].a1) + 'X₁ + ' + formatLabel(cons[i].a2) + 'X₂ ≤ ' + formatLabel(cons[i].rhs), legendX + 32, cy);
        }
        ctx.restore();
    }

    function formatLabel(val) {
        if (Math.abs(val - Math.round(val)) < 1e-6) {
            return Math.round(val).toString();
        }
        return val.toFixed(2).replace('.', ',');
    }

    function draw() {
        ctx.clearRect(0, 0, W, H);
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, W, H);

        drawGrid();
        drawAxes();
        drawFeasibleRegion();
        drawConstraints();
        drawCornerPoints();
        drawLegend();
    }

    if (CanvasRenderingContext2D.prototype.roundRect) {
        draw();
    } else {
        CanvasRenderingContext2D.prototype.roundRect = function (x, y, w, h, r) {
            if (r > w / 2) r = w / 2;
            if (r > h / 2) r = h / 2;
            this.moveTo(x + r, y);
            this.arcTo(x + w, y, x + w, y + h, r);
            this.arcTo(x + w, y + h, x, y + h, r);
            this.arcTo(x, y + h, x, y, r);
            this.arcTo(x, y, x + w, y, r);
            return this;
        };
        draw();
    }

});
