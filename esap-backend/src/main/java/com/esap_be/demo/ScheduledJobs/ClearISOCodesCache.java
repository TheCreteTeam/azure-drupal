package com.esap_be.demo.ScheduledJobs;

import com.esap_be.demo.Controllers.ISOController;
import com.esap_be.demo.Repositories.IIsoRepository;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.scheduling.quartz.QuartzJobBean;

import java.util.Date;


public class ClearISOCodesCache extends QuartzJobBean {
    private static final Logger logger = LoggerFactory.getLogger(ISOController.class);

    private final IIsoRepository isoRepository;


    public ClearISOCodesCache(IIsoRepository isoRepository) {
        this.isoRepository = isoRepository;
    }

    @Override
    protected void executeInternal(org.quartz.JobExecutionContext jobExecutionContext) {
        logger.info("SchedulerJob::ClearISOCodesCache - Start at {}", new Date());
        isoRepository.deleteAll();
        logger.info("SchedulerJob::ClearISOCodesCache - End at {}", new Date());
    }
}
