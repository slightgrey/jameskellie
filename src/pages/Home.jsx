import Hero from '../sections/Hero';
import Stats from '../sections/Stats';
import ServicesSection from '../sections/ServicesSection';
import ExperienceSection from '../sections/ExperienceSection';
import Cta from '../sections/Cta';
import SkillsSection from '../sections/SkillsSection';
import ContactSection from '../sections/ContactSection';

export const SECTIONS = {
  hero: Hero,
  stats: Stats,
  services: ServicesSection,
  experience: ExperienceSection,
  cta: Cta,
  skills: SkillsSection,
  contact: ContactSection,
};

/** Home: the enabled sections in the order saved under Theme Settings → Home layout. */
export default function Home({ sections, site }) {
  return sections.map(({ key, data }) => {
    const Section = SECTIONS[key];
    return Section ? <Section key={key} data={data} site={site} /> : null;
  });
}
