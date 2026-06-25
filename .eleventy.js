module.exports = function (eleventyConfig) {
  // ---- Static assets (copied as-is to the output) ----
  eleventyConfig.addPassthroughCopy("src/css");
  eleventyConfig.addPassthroughCopy("src/js");
  eleventyConfig.addPassthroughCopy("assets"); // lives at project root
  eleventyConfig.addPassthroughCopy("src/robots.txt");
  eleventyConfig.addPassthroughCopy("src/site.webmanifest");

  // ---- Blog posts collection (newest first) ----
  eleventyConfig.addCollection("posts", function (collectionApi) {
    return collectionApi
      .getFilteredByTag("posts")
      .sort((a, b) => b.date - a.date);
  });

  // ---- GitHub Pages project subpath ----
  // The site is served from https://<user>.github.io/personal-brand-cms/, so every
  // root-absolute local path (/assets, /css, /about/ ...) is rewritten to sit under
  // that prefix. Set PATH_PREFIX="" to deploy at a domain root instead.
  const PFX = process.env.PATH_PREFIX !== undefined ? process.env.PATH_PREFIX : "/personal-brand-cms";
  if (PFX) {
    eleventyConfig.addTransform("subpath", function (content) {
      const out = this.page && this.page.outputPath;
      if (!out || !out.endsWith(".html")) return content;
      // href="/..."  src="/..."  (but not protocol-relative "//")
      content = content.replace(/\b(href|src)="\/(?!\/)/g, `$1="${PFX}/`);
      // srcset / imagesrcset: prefix each comma-separated URL
      content = content.replace(/\b(srcset|imagesrcset)="([^"]*)"/g, (m, attr, val) =>
        `${attr}="${val.replace(/(^|,\s*)\/(?!\/)/g, `$1${PFX}/`)}"`);
      return content;
    });
  }

  // ---- Date helpers for templates ----
  eleventyConfig.addFilter("readableDate", function (dateObj) {
    return new Date(dateObj).toLocaleDateString("en-US", {
      year: "numeric",
      month: "long",
      day: "numeric",
    });
  });
  eleventyConfig.addFilter("isoDate", function (dateObj) {
    return new Date(dateObj).toISOString().split("T")[0];
  });

  return {
    dir: {
      input: "src",
      includes: "_includes",
      data: "_data",
      output: "_site",
    },
    htmlTemplateEngine: "njk",
    markdownTemplateEngine: "njk",
  };
};
