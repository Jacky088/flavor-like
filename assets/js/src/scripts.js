/**
 * Flavor Like Scripts - Initialization
 *
 * @fileoverview Auto-initializes Flavor Like plugin on page load and dynamic content
 * @requires ES7 (ES2016) compatible browser
 * @author Flavor Like Team
 * @see https://github.com/Jacky088/flavor-like
 */
(function (window, document) {
  "use strict";

  const arrayFrom = (arrayLike) => {
    if (Array.from) {
      return Array.from(arrayLike);
    }
    return Array.prototype.slice.call(arrayLike);
  };

  const initFlavorLike = (elements) => {
    if (!elements || typeof FlavorLike === "undefined") {
      return;
    }

    const elementArray = elements.length !== undefined ? arrayFrom(elements) : [elements];

    elementArray.forEach((element) => {
      if (element && !element.hasAttribute("data-flavor-like-initialized")) {
        // Isolate each button init so one broken instance (CSS conflict,
        // malformed markup, theme-injected wrappers) cannot leave the rest
        // of the page unbound — see deactivation reports of "squished
        // button that doesn't do anything".
        try {
          new FlavorLike(element);
          element.setAttribute("data-flavor-like-initialized", "true");
        } catch (err) {
          if (window.console && typeof window.console.error === "function") {
            window.console.error("Flavor Like: failed to initialize button", element, err);
          }
          // Mark as initialized so we don't retry on every mutation batch.
          if (element && element.setAttribute) {
            element.setAttribute("data-flavor-like-initialized", "error");
          }
        }
      }
    });
  };

  const pendingElements = new Set();
  let mutationFrameScheduled = false;

  const flushPendingInits = () => {
    mutationFrameScheduled = false;

    if (!pendingElements.size) {
      return;
    }

    const batch = arrayFrom(pendingElements);
    pendingElements.clear();
    initFlavorLike(batch);
  };

  const queueInit = (element) => {
    if (!element || element.nodeType !== 1 || !element.matches(".flavorlike")) {
      return;
    }

    pendingElements.add(element);

    if (mutationFrameScheduled) {
      return;
    }

    mutationFrameScheduled = true;
    window.requestAnimationFrame(flushPendingInits);
  };

  const collectMatchingNodes = (node, elementSelector, callback) => {
    if (node.nodeType !== 1) {
      return;
    }

    if (node.matches && node.matches(elementSelector)) {
      callback(node);
    }

    if (node.querySelectorAll) {
      arrayFrom(node.querySelectorAll(elementSelector)).forEach(callback);
    }
  };

  const FlavorLikeOnElementInserted = (containerSelector, elementSelector, callback) => {
    const onMutationsObserved = (mutations) => {
      mutations.forEach((mutation) => {
        if (!mutation.addedNodes.length) {
          return;
        }

        mutation.addedNodes.forEach((node) => {
          collectMatchingNodes(node, elementSelector, callback);
        });
      });
    };

    const target = document.querySelector(containerSelector);
    if (!target) {
      return null;
    }

    const MutationObserver = window.MutationObserver || window.WebKitMutationObserver;
    if (!MutationObserver) {
      return null;
    }

    const observer = new MutationObserver(onMutationsObserved);
    observer.observe(target, {
      childList: true,
      subtree: true,
    });

    return observer;
  };

  initFlavorLike(document.querySelectorAll(".flavorlike"));

  // Observe body so custom load-more, AJAX, and page-builder injections keep working.
  FlavorLikeOnElementInserted("body", ".flavorlike", queueInit);
})(window, document);
