"use client";
import { useEffect } from "react";

const GROWFORM_SRC =
  "https://embed.growform.co/client/67cf74bca2ec54000b491be6";

function loadGrowform(containerId: string) {
  const wrapper = document.getElementById(containerId);
  if (!wrapper || wrapper.querySelector("script")) return;
  const script = document.createElement("script");
  script.type = "text/javascript";
  script.src = GROWFORM_SRC;
  wrapper.appendChild(script);
}

export default function GrowformEmbed() {
  useEffect(() => {
    const wrapper = document.getElementById("growform-wrapper");
    if (!wrapper) return;
    const observer = new IntersectionObserver(
      (entries) => {
        if (entries[0].isIntersecting) {
          loadGrowform("growform-wrapper");
          observer.disconnect();
        }
      },
      { rootMargin: "300px" }
    );
    observer.observe(wrapper);
    return () => observer.disconnect();
  }, []);

  return <div id="growform-wrapper" />;
}
