document.addEventListener('DOMContentLoaded', function() {
    const contactElements = document.querySelectorAll('a[href^="tel:"], a[href^="fax:"], a[href^="mailto:"]');

    contactElements.forEach(function(element) {
        const contactType = element.href.startsWith('tel:') ? 'phone' :
                            element.href.startsWith('fax:') ? 'fax' : 'email';
        const originalText = element.textContent;
        let maskedText = '';
        let tooltipText = '';
        let revealed = false;

        // Üzenetek különböző nyelveken
        const messages = {
            hu: {
                phone: 'Kattintson a teljes telefonszám megjelenítéséhez!',
                fax: 'Kattintson a teljes fax szám megjelenítéséhez!',
                email: 'Kattintson a teljes email cím megjelenítéséhez!',
                copy: 'Másolás',
                copied: 'Másolva!',
                copyFailed: 'Nem sikerült a másolás'
            },
            en: {
                phone: 'Click to reveal the full phone number!',
                fax: 'Click to reveal the full fax number!',
                email: 'Click to reveal the full email address!',
                copy: 'Copy',
                copied: 'Copied!',
                copyFailed: 'Copy failed'
            },
            de: {
                phone: 'Klicken Sie hier, um die vollständige Telefonnummer anzuzeigen!',
                fax: 'Klicken Sie hier, um die vollständige Faxnummer anzuzeigen!',
                email: 'Klicken Sie hier, um die vollständige E-Mail-Adresse anzuzeigen!',
                copy: 'Kopieren',
                copied: 'Kopiert!',
                copyFailed: 'Kopieren fehlgeschlagen'
            }
        };

        let lang = (document.documentElement.lang || 'hu').toLowerCase();
        let currentMessages = messages.hu; // Alapértelmezett: magyar

        if (lang.startsWith('en')) {
            currentMessages = messages.en;
        } else if (lang.startsWith('de')) {
            currentMessages = messages.de;
        }

        const originalColor = window.getComputedStyle(element).color;

        if (contactType === 'phone' || contactType === 'fax') {
            const visiblePart = originalText.slice(0, 5);
            const hiddenPart = originalText.slice(5);
            maskedText = visiblePart + '.'.repeat(hiddenPart.length);
            tooltipText = contactType === 'phone' ? currentMessages.phone : currentMessages.fax;
            element.classList.add(`hidden-${contactType}`);
        } else if (contactType === 'email') {
            const [localPart, domainPart] = originalText.split('@');
            const visibleLocalPart = localPart.slice(0, 3);
            const hiddenLocalPart = localPart.slice(3);
            const hiddenDomainPart = domainPart.slice(1);
            maskedText = `${visibleLocalPart}${'.'.repeat(hiddenLocalPart.length)}.${'.'.repeat(hiddenDomainPart.length + 1)}`;
            tooltipText = currentMessages.email;
            element.classList.add('hidden-email');
        }

        element.textContent = maskedText;
        element.dataset.href = element.href;
        element.removeAttribute('href');

        const gradientOverlay = `
            linear-gradient(to right, 
                ${originalColor} 0%, 
                ${originalColor} 40%, 
                rgba(255, 255, 255, 0) 100%)`;

        element.style.backgroundImage = gradientOverlay;
        element.style.backgroundClip = 'text';
        element.style.webkitBackgroundClip = 'text';
        element.style.color = 'transparent';

        const tooltipP = document.createElement('p');
        tooltipP.classList.add('contact-info-mask-tooltip-text');
        tooltipP.textContent = tooltipText;

        const wrapper = document.createElement('span');
        wrapper.classList.add('contact-info-mask-tooltip');
        element.parentNode.insertBefore(wrapper, element);
        wrapper.appendChild(element);
        wrapper.appendChild(tooltipP);

        element.style.cursor = 'pointer';

        const rect = element.getBoundingClientRect();
        if (rect.top < 50) {
            wrapper.setAttribute('data-position', 'bottom');
        }

        element.addEventListener('click', function(event) {
            event.preventDefault();

            if (!revealed) {
                this.textContent = originalText;
                this.setAttribute('href', this.dataset.href);

                tooltipP.style.visibility = 'hidden';
                tooltipP.style.opacity = '0';

                this.style.backgroundImage = 'none';
                this.style.color = originalColor;

                revealed = true;
                const copyIcon = document.createElement('span');
                copyIcon.classList.add('copy-icon', `${contactType}-copy-icon`);
                copyIcon.innerHTML = `
                    <svg width="20" height="20" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
    <path d="M53.979 9.143H50.011c-.083 0-.156.028-.233.047V5.023C49.778 2.253 47.473 0 44.64 0H10.217C7.384 0 5.079 2.253 5.079 5.023v46.843c0 2.77 2.305 5.023 5.138 5.023h6.037v2.268C16.254 61.827 18.47 64 21.195 64h32.784c2.725 0 4.941-2.173 4.941-4.843V13.986c0-2.671-2.216-4.843-4.979-4.843zM7.111 51.866V5.023c0-1.649 1.394-2.991 3.106-2.991h34.423c1.712 0 3.106 1.342 3.106 2.991v46.843c0 1.649-1.394 2.991-3.106 2.991H10.217c-1.712 0-3.106-1.342-3.106-2.991zM56.889 59.157c0 1.551-1.306 2.812-2.91 2.812H21.195c-1.604 0-2.91-1.261-2.91-2.812v-2.268h26.354c2.833 0 5.138-2.253 5.138-5.023V11.128c0 .02.15.047.233.047h3.968c1.604 0 2.91 1.261 2.91 2.812v48.03z"/>
    <path d="M38.603 13.206H16.254c-.562 0-1.016.454-1.016 1.016s.454 1.016 1.016 1.016h22.349c.562 0 1.016-.454 1.016-1.016s-.454-1.016-1.016-1.016zM38.603 21.333H16.254c-.562 0-1.016.454-1.016 1.016s.454 1.016 1.016 1.016h22.349c.562 0 1.016-.454 1.016-1.016s-.454-1.016-1.016-1.016zM38.603 29.46H16.254c-.562 0-1.016.454-1.016 1.016s.454 1.016 1.016 1.016h22.349c.562 0 1.016-.454 1.016-1.016s-.454-1.016-1.016-1.016zM28.444 37.587H16.254c-.562 0-1.016.454-1.016 1.016s.454 1.016 1.016 1.016h12.19c.562 0 1.016-.454 1.016-1.016s-.454-1.016-1.016-1.016z"/>
</svg>
                `;

                const copyTooltip = document.createElement('p');
                copyTooltip.classList.add('copy-icon-tooltip');
                copyTooltip.textContent = currentMessages.copy;
                copyIcon.appendChild(copyTooltip);

                wrapper.appendChild(copyIcon);

                // Ellenőrizzük az ikon pozícióját is
                const iconRect = copyIcon.getBoundingClientRect();
                if (iconRect.top < 50) {
                    copyIcon.setAttribute('data-position', 'bottom');
                }

                copyIcon.addEventListener('click', function() {
                    navigator.clipboard.writeText(originalText).then(function() {
                        copyTooltip.textContent = currentMessages.copied;
                        copyIcon.classList.add('tooltip-visible');

                        setTimeout(function() {
                            copyIcon.classList.remove('tooltip-visible');
                        }, 2000);
                    }, function() {
                        copyTooltip.textContent = currentMessages.copyFailed;
                    });
                });

            } else {
                this.classList.add(`showed-${contactType}`);
                window.location.href = this.dataset.href;
            }
        });
    });
});