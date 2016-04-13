/*
 Klapptabelle (c) 2007 J. Strübig struebig@gmx.net
 http://javascript.jstruebig.de/javascript/73

 Letzte Änderungen:

 22.12.2011
 * CSS Klassen für geöffnete Reihen

 12.05.2011
 * Bug: letzte Reihe ließ sich auch leer klappen

 28.02.2011
 * Es können mit 'open' mehrere Reihen geöffnet sein.

 11.02.2011
 * Mit 'open' markieren einer Reihe, die per default geöffnet sein soll.

 08.02.2010
 * Statt 5 Bilder nur noch ein CSS Sprite

 07.01.2008
 * Preload der Bilder in die ini-Funktion verschoben
 * Regulärer Ausdruck

 */

function KlappTabelle(t) {
    var count = 0;
    var obj = t;
    var rows = [];
    var self = this;

    self.doCloseAll = false;
    self.onopen = self.onclose = function() {};

    self.hideAll = function() {
        for(var i = 0; i < rows.length;i++) rows[i].hide();
    };
    self.showAll = function() {
        var tmp = self.doCloseAll;
        for(var i = 0; i < rows.length;i++) {
            rows[i].show();
            self.doCloseAll = false;
        }
        self.doCloseAll = tmp;
    };

    function init() {
        var i,
            r = obj.getElementsByTagName('tr'),
            akt,
            name = new RegExp('\\b' + KlappTabelle.className + '\\b', 'i'),
            open = [];

        for(i = 0; i < r.length; i++) {
            if(name.test(r[i].className) ) {
                //if(akt) alert(akt + '/'+ akt.rows());
                if(akt && !akt.rows()) akt.disable();
                // Eine neue Hauptreihe initialisieren
                akt = new Part(r[i], self);
                rows.push(akt);
                if(/\bopen\b/i.test(r[i].className)){
                    open.push(akt);
                }

            } else if(akt) akt.addRow( r[i] );
        }
        if(akt && !akt.rows()) akt.disable();

        self.hideAll();

        // per default geöffnete Reihen, öffnen
        if(open.length) open.forEach( function(item) { item.show();});
    }

    if(obj) init();

    ////////////////////////////////////////////
    /*
     privates Objekt: Part(row, parent)

     Parameter:
     row: 		Die Reihe unter der ausgeblendet werden soll
     parent: 	Das Objekt/Tabelle das die Reihe erzeugt

     Funktionen:
     addRow()
     rows()
     show()
     hide()
     disable()
     */
    ////////////////////////////////////////////
    function Part(row, parent) {
        var self = this;	// Zeiger auf das Objekt
        var open = true;	// Flag
        var rows = [];		// Die Reihen die "ein- und ausgeklappt" werden

        // anklickbare Grafik und in die erste Spalte einfügen
        var m = new Marker(row.cells[0]);

        m.show();
        m.onclick = function() {
            if(open) self.hide(); else self.show();
        };

        // Funktionen
        this.addRow = function(r) { rows[rows.length] = r;};
        this.rows = function() {return rows.length;};
        this.show = function() {
            if(parent.doCloseAll) parent.hideAll();
            if(parent.onopen(parent, row) == false) return false;
            for(var i = 0; i < rows.length; i++) rows[i].style.display = '';
            m.show();
            row.className = 'open';
            open = true;
            return true;
        };

        this.hide = function() {
            if(!parent.onclose(parent, row) == false) return false;
            for(var i = 0; i < rows.length; i++) rows[i].style.display = 'none';
            open = false;
            m.hide();
            row.className = '';
            return true;
        };

        this.disable = function() { m.disable();};

    }
    function Marker(wo) {
        var el = document.createElement('div');
        var disable = false;
        var open = 0;
        var _this = this;

        el.style.cssFloat  = el.style.styleFloat = 'left';
        el.style.width = KlappTabelle.markerSize + 'px';
        el.style.height = KlappTabelle.markerSize + 'px';
        el.style.margin = '1px';
        el.style.padding = '0';
        el.style.overflow = 'hidden';
        el.style.border = '1px solid black';
        el.style.background = 'url(' + KlappTabelle.marker + ') 0 0 no-repeat'
        el.style.cursor = 'pointer';

        wo.insertBefore(el, wo.firstChild)

        this.onclick = function() {};

        this.disable = function() {
            disable = true;
            open = 0;
            update();
            el.style.cursor = '';
        };
        this.show = function() {
            if(disable) return;
            open = -3 * KlappTabelle.markerSize;
            update();
        }
        this.hide = function() {
            if(disable) return;
            open = -1 * KlappTabelle.markerSize;
            update();
        }

        el.onclick = function() {
            if(disable) return;
            _this.show();
            _this.onclick();
        };
        el.onmouseover = function() {
            if(!disable) update(-KlappTabelle.markerSize);
        };
        el.onmouseout = function() {
            if(!disable) update();
        };
        function update(offset){
            var pos = open + (offset ? offset : 0);
            el.style.backgroundPosition = '0px '+ pos +'px';
        }
    }
}

KlappTabelle.init = function(callback) {
    // prüfen ob die Bilder vorhanden sind.
    var img = new Image();
    img.onerror = function() {
        alert('Das Hintergrundbild fehlt!\n\nEntweder ist es an der falschen Stelle oder es  wurde vergessen.\n\nEs wurde versucht *' + this.src + '* zu laden.');
    }
    img.src = KlappTabelle.marker;

    // Alle Tabellen des Dokuments
    var t = document.getElementsByTagName('table');
    var r = /\bklapptabelle\b/i;
    for(var i = 0; i < t.length; i++) if(t[i].className && r.test(t[i].className)) {
        var tmp = new KlappTabelle(t[i]);
        if(callback) callback(tmp);
    }
};

KlappTabelle.className  = 'main'; // Der Klassenamen der Haupreihe
KlappTabelle.marker     = 'bilder/pfeile.gif'; // Ein sprite mit den 5 Bildern
KlappTabelle.markerSize = 11; // Die Größe der einzelnen Bilder

if (!Array.prototype.forEach ) {
    Array.prototype.forEach = function(fun){
        var len = this.length;
        if(typeof fun != "function") throw new TypeError();

        var thisp = arguments[1];
        for (var i = 0; i < len; i++) if (i in this) fun.call(thisp, this[i], i, this);
    };
}